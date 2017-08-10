define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', function($scope) {}]);
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', 'mediagallery', 'srvTag', function($scope, http2, $uibModal, mediagallery, srvTag) {
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.entry = { url: oMission.entryUrl }
        });
        $scope.remove = function() {
            if (window.confirm('确定删除项目？')) {
                http2.get('/rest/pl/fe/matter/mission/remove?id=' + $scope.mission.id, function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.mission.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.mission.pic = '';
            $scope.update('pic');
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var prop;
            if (data.state.indexOf('mission.') === 0) {
                prop = data.state.substr(8);
                $scope.mission[prop] = data.value;
                $scope.update(prop);
            }
        });
        $scope.makePagelet = function(type) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/mission/pagelet.html',
                resolve: {
                    mission: function() {
                        return $scope.mission;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'mission', 'mediagallery', function($scope2, $mi, mission, mediagallery) {
                    var tinymceEditor;
                    $scope2.reset = function() {
                        tinymceEditor.setContent('');
                    };
                    $scope2.ok = function() {
                        var html = tinymceEditor.getContent();
                        tinymceEditor.remove();
                        $mi.close({
                            html: html
                        });
                    };
                    $scope2.cancel = function() {
                        tinymceEditor.remove();
                        $mi.dismiss();
                    };
                    $scope2.$on('tinymce.multipleimage.open', function(event, callback) {
                        var options = {
                            callback: callback,
                            multiple: true,
                            setshowname: true
                        };
                        mediagallery.open($scope.siteId, options);
                    });
                    $scope2.$on('tinymce.instance.init', function(event, editor) {
                        var page;

                        tinymceEditor = editor;
                        page = mission[type + '_page'];
                        if (page) {
                            editor.setContent(page.html);
                        } else {
                            http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + type, function(rsp) {
                                mission[type + '_page_name'] = rsp.data.name;
                                page = rsp.data;
                                editor.setContent(page.html);
                            });
                        }
                    });
                }],
                size: 'lg',
                backdrop: 'static'
            }).result.then(function(result) {
                http2.post('/rest/pl/fe/matter/mission/page/update?id=' + $scope.mission.id + '&page=' + type, result, function(rsp) {
                    $scope.mission[type + '_page'] = rsp.data;
                });
            });
        };
        $scope.codePage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                codeName = $scope.mission[prop];
            if (codeName && codeName.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + codeName;
            } else {
                http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + page, function(rsp) {
                    $scope.mission[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.tagMatter = function(subType){
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.mission, oTags, subType);
        };
    }]);
    ngApp.provider.controller('ctrlPhase', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        $scope.numberOfNewPhases = 1;
        var newPhase = function() {
            var data = {
                title: '阶段' + ($scope.phases.length + 1)
            };
            /*设置阶段的缺省起止时间*/
            (function() {
                var nextDay = new Date(),
                    lastEndAt;
                if ($scope.phases.length) {
                    lastEndAt = 0;
                    angular.forEach($scope.phases, function(phase) {
                        if (phase.end_at > lastEndAt) {
                            lastEndAt = phase.end_at;
                        }
                    });
                    /* 最后一天的下一天 */
                    nextDay.setTime(lastEndAt * 1000 + 86400000);
                } else {
                    /* tomorrow */
                    nextDay.setTime(nextDay.getTime() + 86400000);
                }
                data.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
                data.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
            })();

            return data;
        };
        $scope.add = function() {
            var phase;
            if ($scope.numberOfNewPhases > 0) {
                phase = newPhase();
                http2.post('/rest/pl/fe/matter/mission/phase/create?mission=' + $scope.mission.id, phase, function(rsp) {
                    $scope.phases.push(rsp.data);
                    $scope.numberOfNewPhases--;
                    if ($scope.numberOfNewPhases > 0) {
                        $scope.add();
                    }
                });
            }
        };
        $scope.update = function(phase, name) {
            var modifiedData = {};
            modifiedData[name] = phase[name];
            http2.post('/rest/pl/fe/matter/mission/phase/update?mission=' + $scope.mission.id + '&id=' + phase.phase_id, modifiedData, function(rsp) {
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function(phase) {
            if (window.confirm('确定删除项目阶段？')) {
                http2.get('/rest/pl/fe/matter/mission/phase/remove?mission=' + $scope.mission.id + '&id=' + phase.phase_id, function(rsp) {
                    $scope.phases.splice($scope.phases.indexOf(phase), 1);
                });
            }
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var prop;
            if (data.state.indexOf('phase.') === 0) {
                prop = data.state.substr(6);
                data.obj[prop] = data.value;
                $scope.update(data.obj, prop);
            }
        });
        $scope.$watch('mission', function(mission) {
            if (mission) {
                $scope.phases = mission.phases;
            }
        });
    }]);
    ngApp.provider.controller('ctrlCoworker', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        $scope.label = '';
        $scope.newOwner = '';
        $scope.transfer = function() {
            var url = '/rest/pl/fe/matter/mission/coworker/transferMission?site=' + $scope.mission.siteid;
            url += '&mission=' + $scope.mission.id + '&label=' + $scope.newOwner;
            http2.get(url, function(rsp) {
                noticebox.success('完成移交');
                if (rsp.data == 1) {
                    $scope.status = true;
                }
                $scope.newOwner = '';
            });
        };
        $scope.openMyCoworkers = function() {
            if ($scope.myCoworkers && $scope.myCoworkers.length) {
                $('#popoverMyCoworker').trigger('show');
            }
        };
        $scope.closeMyCoworkers = function() {
            $('#popoverMyCoworker').trigger('hide');
        };
        $scope.chooseMyCoworker = function(coworker) {
            $scope.label = coworker.coworker_label;
            $('#popoverMyCoworker').trigger('hide');
        };
        $scope.add = function() {
            var url = '/rest/pl/fe/matter/mission/coworker/add?mission=' + $scope.mission.id;
            url += '&label=' + $scope.label;
            http2.get(url, function(rsp) {
                $scope.coworkers.splice(0, 0, rsp.data);
                if ($scope.myCoworkers && $scope.myCoworkers.length) {
                    for (var i = 0, ii = $scope.myCoworkers.length; i < ii; i++) {
                        if ($scope.label === $scope.myCoworkers[i].coworker_label) {
                            $scope.myCoworkers.splice(i, 1);
                            break;
                        }
                    }
                }
                $scope.label = '';
            });
        };
        $scope.remove = function(acl) {
            http2.get('/rest/pl/fe/matter/mission/coworker/remove?mission=' + $scope.mission.id + '&coworker=' + acl.coworker, function(rsp) {
                var index = $scope.coworkers.indexOf(acl);
                $scope.coworkers.splice(index, 1);
            });
        };
        $scope.makeInvite = function() {
            http2.get('/rest/pl/fe/matter/mission/coworker/makeInvite?mission=' + $scope.mission.id, function(rsp) {
                var host, url;
                host = $scope.mission.opUrl.match(/\/\/(\S+?)\//);
                host = host.length === 2 ? host[1] : location.host;
                url = 'http://' + host + rsp.data;
                $scope.inviteURL = url;
                $('#shareMission').trigger('show');
            });
        };
        $scope.closeInvite = function() {
            $scope.inviteURL = '';
            $('#shareMission').trigger('hide');
        };
        $scope.$watch('mission', function(mission) {
            if (mission) {
                http2.get('/rest/pl/fe/matter/mission/coworker/list?mission=' + $scope.mission.id, function(rsp) {
                    $scope.coworkers = rsp.data;
                });
                http2.get('/rest/pl/fe/matter/mission/coworker/mine?mission=' + $scope.mission.id, function(rsp) {
                    $scope.myCoworkers = rsp.data;
                });
            }
        });
    }]);
});