define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvEnrollApp', function($scope, $uibModal, http2, srvSite, srvEnrollApp) {
        var oEntryRule;
        $scope.rule = {};
        $scope.isInputPage = function(pageName) {
            if (!$scope.app) {
                return false;
            }
            for (var i in $scope.app.pages) {
                if ($scope.app.pages[i].name === pageName && $scope.app.pages[i].type === 'I') {
                    return true;
                }
            }
            return false;
        };
        $scope.reset = function() {
            srvEnrollApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.app).then(function(result) {
                var rule = {};
                if (!oEntryRule.member[result.chosen.id]) {
                    if ($scope.jumpPages.defaultInput) {
                        rule.entry = $scope.jumpPages.defaultInput.name;
                    } else {
                        rule.entry = '';
                    }
                    oEntryRule.member[result.chosen.id] = rule;
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (oEntryRule.member[mschemaId]) {
                delete oEntryRule.member[mschemaId];
                $scope.update('entry_rule');
            }
        };
        $scope.$watch('memberSchemas', function(nv) {
            if (!nv) return;
            $scope.mschemasById = {};
            $scope.memberSchemas.forEach(function(mschema) {
                $scope.mschemasById[mschema.id] = mschema;
            });
        }, true);
        $scope.addExclude = function() {
            var rule = $scope.rule;
            if (!rule.exclude) {
                rule.exclude = [];
            }
            rule.exclude.push('');
        };
        $scope.removeExclude = function(index) {
            $scope.rule.exclude.splice(index, 1);
            $scope.configExclude();
        };
        $scope.configExclude = function() {
            $scope.app.entry_rule.exclude = $scope.rule.exclude;
            $scope.update('entry_rule');
        };
        srvEnrollApp.get().then(function(app) {
            $scope.jumpPages = srvEnrollApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
            $scope.rule.exclude = app.entry_rule.exclude;
            oEntryRule = app.entry_rule;
        }, true);
    }]);
});
