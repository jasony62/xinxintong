define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 填写项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', '$q', 'srvSigninPage', function($scope, $q, srvSigninPage) {
        $scope.updConfig = function(oActiveSchema) {
            var pages, oPage;
            pages = $scope.app.pages;
            for (var i = pages.length - 1; i >= 0; i--) {
                oPage = pages[i];
                if (oPage.type === 'I') {
                    oPage.updateSchema(oActiveSchema);
                    srvSigninPage.update(oPage, ['data_schemas', 'html']);
                }
            }
        };
    }]);
});