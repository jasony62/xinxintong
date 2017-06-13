define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var oEntryRule;
        $scope.rule = {};
        $scope.reset = function() {};
        $scope.changeUserScope = function() {
            switch (oEntryRule.scope) {
                case 'member':
                    oEntryRule.member === undefined && (oEntryRule.member = {});
                    break;
                case 'sns':
                    oEntryRule.sns === undefined && (oEntryRule.sns = {});
                    break;
                default:
            }
            this.update('entry_rule');
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema().then(function(result) {
                var rule = {};
                if (!oEntryRule.member[result.chosen.id]) {
                    rule.entry = '';
                    oEntryRule.member[result.chosen.id] = rule;
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.editMschema = function(mschemaId) {
            location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + mschemaId;
        };
        $scope.removeMschema = function(mschemaId) {
            if (oEntryRule.member[mschemaId]) {
                delete oEntryRule.member[mschemaId];
                $scope.update('entry_rule');
            }
        };
        srvSite.snsList().then(function(aSns) {
            $scope.sns = aSns;
        });
        srvSite.memberSchemaList().then(function(aMemberSchemas) {
            $scope.memberSchemas = aMemberSchemas;
            $scope.mschemasById = {};
            $scope.memberSchemas.forEach(function(mschema) {
                $scope.mschemasById[mschema.id] = mschema;
            });
        });
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            $scope.rule = oEntryRule = mission.entry_rule;
        });
    }]);
});
