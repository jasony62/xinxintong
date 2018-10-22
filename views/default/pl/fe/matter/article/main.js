define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvSite', 'mediagallery', 'noticebox', 'srvApp', 'tmsThumbnail', '$timeout', 'srvTag', function($scope, $uibModal, http2, noticebox, srvSite, mediagallery, noticebox, srvApp, tmsThumbnail, $timeout, srvTag) {
        var _oEditing, modifiedData = {};

        $scope.modified = false;
        $scope.submit = function() {
            http2.post('/rest/pl/fe/matter/article/update?site=' + _oEditing.siteid + '&id=' + _oEditing.id, modifiedData).then(function() {
                modifiedData = {};
                $scope.modified = false;
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/article/remove?site=' + _oEditing.siteid + '&id=' + _oEditing.id).then(function(rsp) {
                    if (_oEditing.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + _oEditing.siteid + "&id=" + _oEditing.mission.id;
                    } else {
                        location = '/rest/pl/fe';
                    }
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    _oEditing.pic = url + '?_=' + (new Date * 1);
                    srvApp.update('pic');
                }
            };
            mediagallery.open(_oEditing.siteid, options);
        };
        $scope.removePic = function() {
            _oEditing.pic = '';
            srvApp.update('pic');
        };
        $scope.assignMission = function() {
            srvApp.assignMission();
        };
        $scope.quitMission = function() {
            srvApp.quitMission();
        };
        $scope.assignNavApp = function() {
            var oOptions = {
                matterTypes: [{
                    value: 'enroll',
                    title: '登记活动',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true
            };
            srvSite.openGallery(oOptions).then(function(result) {
                if (result.matters && result.matters.length === 1) {
                    !_oEditing.config.nav && (_oEditing.config.nav = {});
                    !_oEditing.config.nav.app && (_oEditing.config.nav.app = []);
                    _oEditing.config.nav.app.push({
                        id: result.matters[0].id,
                        title: result.matters[0].title
                    });
                    srvApp.update('config');
                }
            });
        };
        $scope.removeNavApp = function(index) {
            _oEditing.config.nav.app.splice(index, 1);
            if (_oEditing.config.nav.app.length === 0) {
                delete _oEditing.config.nav.app;
            }
            srvApp.update('config');
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            if (subType === 'C') {
                oTags = $scope.oTagC;
            } else {
                oTags = $scope.oTag;
            }
            srvTag._tagMatter(_oEditing, oTags, subType);
        };
        // 更改缩略图
        $scope.$watch('editing.title', function(title, oldTitle) {
            if (_oEditing && title && oldTitle) {
                if (!_oEditing.pic && title.slice(0, 1) != oldTitle.slice(0, 1)) {
                    $timeout(function() {
                        tmsThumbnail.thumbnail(_oEditing);
                    }, 3000);
                }
                $scope.rule = _oRule = _oEditing.entryRule;
            }
        });
        $scope.$watch('editing', function(nv) {
            _oEditing = nv;
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvApp', function($scope, $uibModal, http2, srvSite, srvApp) {
        var _oEditing, _oRule, _oRule2;

        function chooseGroupApp() {
            return $uibModal.open({
                templateUrl: 'chooseGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.app = _oEditing;
                    $scope2.data = {
                        app: null,
                        round: null
                    };
                    _oEditing.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + _oEditing.siteid + '&size=999&cascaded=Y';
                    _oEditing.mission && (url += '&mission=' + _oEditing.mission.id);
                    http2.get(url).then(function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result;
        }

        function setMschemaEntry(mschemaId, type) {
            if (!$scope[type].member) {
                $scope[type].member = {};
            }
            if (!$scope[type].member[mschemaId]) {
                $scope[type].member[mschemaId] = {
                    entry: 'Y'
                };
                return true;
            }
            return false;
        }

        function setGroupEntry(oResult, type) {
            if (oResult.app) {
                $scope[type].group = { id: oResult.app.id, title: oResult.app.title };
                if (oResult.round) {
                    $scope[type].group.round = { id: oResult.round.round_id, title: oResult.round.title };
                }
                return true;
            }
            return false;
        }

        $scope.changeUserScope = function(scopeProp, type) {
            switch (scopeProp) {
                case 'sns':
                    if ($scope[type].scope[scopeProp] === 'Y') {
                        if (!$scope[type].sns) {
                            $scope[type].sns = {};
                        }
                        if ($scope.snsCount === 1) {
                            $scope[type].sns[Object.keys($scope.sns)[0]] = { 'entry': 'Y' };
                        }
                    }
                    break;
            }
            srvApp.changeUserScope($scope[type].scope, $scope.sns, '', type);
        };
        $scope.chooseMschema = function(type) {
            srvSite.chooseMschema(_oEditing).then(function(result) {
                if (setMschemaEntry(result.chosen.id, type)) {
                    srvApp.update(type);
                }
            });
        };
        $scope.chooseGroupApp = function(type) {
            chooseGroupApp().then(function(result) {
                if (setGroupEntry(result, type)) {
                    srvApp.update(type);
                }
            });
        };
        $scope.removeGroupApp = function(type) {
            delete $scope[type].group;
            srvApp.update(type);
        };
        $scope.removeMschema = function(mschemaId, type) {
            if ($scope[type].member[mschemaId]) {
                delete $scope[type].member[mschemaId];
                srvApp.update(type);
            }
        };
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            _oEditing = nv;
            $scope.entryRule = _oRule = nv.entryRule;
            $scope.downloadRule = _oRule2 = nv.downloadRule;
        });
    }]);
});