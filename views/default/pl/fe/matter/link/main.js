define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', '$uibModal', 'srvTag', 'srvSite', function($scope, http2, mediagallery, $uibModal, srvTag, srvSite) {
        var modifiedData = {};
        $scope.modified = false;
        $scope.urlsrcs = {
            '0': '外部链接',
            '1': '多图文',
            '2': '频道',
            '3': '内置回复',
        };
        $scope.linkparams = {
            '{{openid}}': '用户标识(openid)',
            '{{site}}': '公众号标识',
        };
        var getInitData = function() {
            http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
                editLink(rsp.data);
            });
        };
        var editLink = function(link) {
            if (link.params) {
                var p;
                for (var i in link.params) {
                    p = link.params[i];
                    p.customValue = $scope.linkparams[p.pvalue] ? false : true;
                }
            }
            if (link.matter_mg_tag !== '') {
                link.matter_mg_tag.forEach(function(cTag, index) {
                    $scope.oTag.forEach(function(oTag) {
                        if (oTag.id === cTag) {
                            link.matter_mg_tag[index] = oTag;
                        }
                    });
                });
            }
            $scope.editing = link;
            $scope.persisted = angular.copy(link);
            $('[ng-model="editing.title"]').focus();
        };
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.modified) {
                message = '修改还没有保存，是否要离开当前页面？',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.remove = function() {
            http2.get('/rest/pl/fe/matter/link/remove?site=' + $scope.siteId + '&id=' + $scope.id, function() {
                location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
            });
        };
        $scope.submit = function() {
            http2.post('/rest/pl/fe/matter/link/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
                modifiedData = {};
                $scope.modified = false;
            });
        };
        $scope.update = function(names) {
            angular.isString(names) && (names = [names]);
            names.forEach(function(n) {
                modifiedData[n] = $scope.editing[n];
                if (n === 'urlsrc' && $scope.editing.urlsrc != 0) {
                    $scope.editing.open_directly = 'N';
                    modifiedData.open_directly = 'N';
                } else if (n === 'method' && $scope.editing.method === 'POST') {
                    $scope.editing.open_directly = 'N';
                    modifiedData.open_directly = 'N';
                } else if (n === 'open_directly' && $scope.editing.open_directly == 'Y') {
                    $scope.editing.access_control = 'N';
                    modifiedData.access_control = 'N';
                    modifiedData.authapis = '';
                } else if (n === 'access_control' && $scope.editing.access_control == 'N') {
                    var p;
                    for (var i in $scope.editing.params) {
                        p = $scope.editing.params[i];
                        if (p.pvalue == '{{authed_identity}}') {
                            window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
                            $scope.editing.access_control = 'Y';
                            modifiedData.access_control = 'Y';
                            return false;
                        }
                    }
                    modifiedData.authapis = '';
                }
                $scope.modified = true;
            });
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.editing.pic = '';
            $scope.update('pic');
        };
        $scope.addParam = function() {
            http2.get('/rest/pl/fe/matter/link/paramAdd?site=' + $scope.siteId + '&linkid=' + $scope.editing.id, function(rsp) {
                var oNewParam = {
                    id: rsp.data,
                    pname: 'newparam',
                    pvalue: ''
                };
                if ($scope.editing.urlsrc === '3' && $scope.editing.url === '9') oNewParam.pname = 'channelid';
                $scope.editing.params.push(oNewParam);
            });
        };
        $scope.updateParam = function(updated, name) {
            if (updated.pvalue === '{{authed_identity}}' && $scope.editing.access_control === 'N') {
                window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
                updated.pvalue = '';
            }
            if (updated.pvalue !== '{{authed_identity}}')
                updated.authapi_id = 0;
            // 参数中有额外定义，需清除
            var p = {
                pname: updated.pname,
                pvalue: encodeURIComponent(updated.pvalue),
                authapi_id: updated.authapi_id
            };
            http2.post('/rest/pl/fe/matter/link/paramUpd?site=' + $scope.siteId + '&id=' + updated.id, p);
        };
        $scope.removeParam = function(removed) {
            http2.get('/rest/mp/matter/link/removeParam?id=' + removed.id, function(rsp) {
                var i = $scope.editing.params.indexOf(removed);
                $scope.editing.params.splice(i, 1);
            });
        };
        $scope.changePValueMode = function(p) {
            p.pvalue = '';
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.editing, oTags, subType);
        };
        $scope.assignMission = function() {
            var _this = this;
            srvSite.openGallery({
                matterTypes: [{
                    value: 'mission',
                    title: '项目',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true
            }).then(function(missions) {
                var matter;
                if (missions.matters.length === 1) {
                    matter = {
                        id: $scope.id,
                        type: 'link'
                    };
                    http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + missions.matters[0].id, matter, function(rsp) {
                        var mission = rsp.data;

                        $scope.editing.mission = mission;
                        $scope.editing.mission_id = mission.id;
                        modifiedData['mission_id'] = mission.id;
                        if (!$scope.editing.pic || $scope.editing.pic.length === 0) {
                            $scope.editing.pic = mission.pic;
                            modifiedData['pic'] = mission.pic;
                        }
                        if (!$scope.editing.summary || $scope.editing.summary.length === 0) {
                            $scope.editing.summary = mission.summary;
                            modifiedData['summary'] = mission.summary;
                        }
                        _this.submit();
                    });
                }
            });
        };
        $scope.quitMission = function() {
            var that = this;
            matter = {
                    id: $scope.editing.id,
                    type: 'link',
                    title: $scope.editing.title
                },
                http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + $scope.siteId + '&id=' + $scope.editing.mission_id, matter, function(rsp) {
                    delete $scope.editing.mission;
                    $scope.editing.mission_id = 0;
                    modifiedData['mission_id'] = 0;
                    $scope.editing.mission_phase_id = '';
                    modifiedData['mission_phase_id'] = '';

                    that.submit();
                });
        };
        $scope.choosePhase = function() {
            var phaseId = $scope.editing.mission_phase_id,
                newPhase, updatedFields = ['mission_phase_id'],
                that = this;

            // 去掉活动标题中现有的阶段后缀
            $scope.editing.mission.phases.forEach(function(phase) {
                $scope.editing.title = $scope.editing.title.replace('-' + phase.title, '');
                if (phase.phase_id === phaseId) {
                    newPhase = phase;
                }
            });
            if (newPhase) {
                // 给活动标题加上阶段后缀
                $scope.editing.title += '-' + newPhase.title;
                updatedFields.push('title');
            } else {
                updatedFields.push('title');
            }

            that.update(updatedFields);
        };
        $scope.$watch('editing.urlsrc', function(nv) {
            switch (nv) {
                case '1':
                    if ($scope.news === undefined) {
                        http2.get('/rest/pl/fe/matter/news/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
                            $scope.news = rsp.data.docs;
                        });
                    }
                    break;
                case '2':
                    if ($scope.channels === undefined) {
                        http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
                            $scope.channels = rsp.data.docs;
                        });
                    }
                    break;
                case '3':
                    if ($scope.inners === undefined) {
                        http2.get('/rest/pl/fe/matter/inner/list?site=' + $scope.siteId, function(rsp) {
                            $scope.inners = rsp.data;
                        });
                    }
                    break;
            }
        });
        getInitData();
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
    }]);
});
