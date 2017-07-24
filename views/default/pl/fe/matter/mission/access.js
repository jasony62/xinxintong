define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var oEntryRule;
        $scope.rule = {};
        $scope.changeUserScope = function() {
            switch (oEntryRule.scope) {
                case 'member':
                    oEntryRule.member === undefined && (oEntryRule.member = {});
                    break;
                case 'sns':
                    oEntryRule.sns === undefined && (oEntryRule.sns = {});
                    Object.keys($scope.sns).forEach(function(snsName) {
                        if (oEntryRule.sns[snsName] === undefined) {
                            oEntryRule.sns[snsName] = { entry: 'Y' };
                        }
                    });
                    break;
                default:
            }
            this.update('entry_rule');
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.mission).then(function(result) {
                var chosen;
                if (result && result.chosen) {
                    chosen = result.chosen;
                    $scope.mschemasById[chosen.id] = chosen;
                    if (!oEntryRule.member[chosen.id]) {
                        oEntryRule.member[chosen.id] = { entry: '' };
                        $scope.update('entry_rule');
                    }
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id === $scope.mission.id) {
                location.href = '/rest/pl/fe/matter/mission/mschema?site=' + $scope.mission.siteid + '&id=' + $scope.mission.id + '#' + oMschema.id;
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.mission.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (oEntryRule.member[mschemaId]) {
                delete oEntryRule.member[mschemaId];
                $scope.update('entry_rule');
            }
        };
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsCount = Object.keys(oSns).length;
        });
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.rule = oEntryRule = oMission.entry_rule;
            srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                $scope.memberSchemas = aMemberSchemas;
                $scope.mschemasById = {};
                $scope.memberSchemas.forEach(function(mschema) {
                    $scope.mschemasById[mschema.id] = mschema;
                });
            });
        });
    }]);
});
