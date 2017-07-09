define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 填写项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', '$q', 'srvSigninApp', 'srvSigninPage', function($scope, $q, srvSigninApp, srvSigninPage) {
        $scope._submitChange = function(changedPages) {
            srvSigninApp.update('data_schemas').then(function() {
                changedPages.forEach(function(oPage) {
                    srvSigninPage.update(oPage, ['data_schemas', 'html']);
                });
            });
        };
        $scope.assignEnrollApp = function() {
            srvSigninApp.assignEnrollApp();
        };
        $scope.cancelEnrollApp = function() {
            srvSigninApp.cancelEnrollApp();
        };
        $scope.assignGroupApp = function() {
            srvSigninApp.assignGroupApp();
        };
        $scope.cancelGroupApp = function() {
            $scope.app.group_app_id = '';
            srvSigninApp.update('group_app_id');
        };
    }]);
});
