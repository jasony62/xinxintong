define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccess', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
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
        srvEnrollApp.get().then(function(app) {
            var oEntry;
            oEntry = {
                pages: []
            };
            $scope.entry = oEntry;
            app.pages.forEach(function(oPage) {
                oEntry.pages.push(oPage);
            });
            oEntry.pages.push({ name: 'repos', 'title': '所有数据页' });
            $scope.jumpPages = srvEnrollApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
        }, true);
    }]);
});
