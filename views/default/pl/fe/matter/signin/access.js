define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvSigninApp', function($scope, $uibModal, http2, srvSite, srvSigninApp) {
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
            srvSigninApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvSigninApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema().then(function(result) {
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
        $scope.editMschema = function(mschemaId) {
            location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + mschemaId;
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
        srvSigninApp.get().then(function(app) {
            $scope.jumpPages = srvSigninApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
            oEntryRule = app.entry_rule;
        }, true);
    }]);
});
