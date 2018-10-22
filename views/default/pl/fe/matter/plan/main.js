define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            $scope.update('pic');
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/plan/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id).then(function(rsp) {
                    if ($scope.app.mission) {
                        location.href = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location.href = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvPlanApp', function($scope, $uibModal, http2, srvSite, srvPlanApp) {
        function chooseGroupApp() {
            return $uibModal.open({
                templateUrl: 'chooseGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.app = _oApp;
                    $scope2.data = {
                        app: null,
                        round: null
                    };
                    _oApp.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + _oApp.siteid + '&size=999&cascaded=Y';
                    _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                    http2.get(url).then(function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result;
        }

        function setGroupEntry(oResult) {
            if (oResult.app) {
                _oEntryRule.group = { id: oResult.app.id, title: oResult.app.title };
                if (oResult.round) {
                    _oEntryRule.group.round = { id: oResult.round.round_id, title: oResult.round.title };
                }
                return true;
            }
            return false;
        }

        var _oApp, _oEntryRule;
        $scope.changeUserScope = function(scope) {
            srvPlanApp.changeUserScope(scope, $scope.sns);
            if (scope === 'member' && _oEntryRule.scope.member === 'Y') {
                if (!_oEntryRule.member || Object.keys(_oEntryRule.member).length === 0) {
                    $scope.chooseMschema();
                }
            } else if (scope === 'group' && _oEntryRule.scope.group === 'Y') {
                if (!_oEntryRule.group) {
                    $scope.chooseGroupApp();
                }
            }
        };
        $scope.chooseGroupApp = function() {
            chooseGroupApp().then(function(result) {
                if (setGroupEntry(result)) {
                    $scope.update('entryRule');
                }
            });
        };
        $scope.removeGroupApp = function() {
            delete _oEntryRule.group;
            $scope.update('entryRule');
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.app).then(function(result) {
                if (!_oEntryRule.member[result.chosen.id]) {
                    _oEntryRule.member[result.chosen.id] = { entry: '' };
                    $scope.update('entryRule');
                }
            });
        };
        $scope.editMschema = function(mschemaId) {
            var oMschema;
            if (oMschema = $scope.mschemasById[mschemaId]) {
                if (oMschema.matter_id) {
                    if (oMschema.matter_type === 'mission') {
                        location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                    } else {
                        location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                    }
                } else {
                    location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '#' + oMschema.id;
                }
            }
        };
        $scope.removeMschema = function(mschemaId) {
            var bSchemaChanged;
            if (_oEntryRule.member[mschemaId]) {
                delete _oEntryRule.member[mschemaId];
                $scope.update('entryRule');
            }
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.entryRule = _oEntryRule = oApp.entryRule;
        });
    }]);
});