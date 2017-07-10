define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecycle', ['$scope', 'srvEnrollApp', 'srvEnrollRecord', function($scope, srvEnrollApp, srvEnrollRecord) {
        $scope.doSearch = function(pageNumber) {
            $scope.rows = {
                allSelected: 'N',
                selected: {}
            };
            srvEnrollRecord.searchRecycle(pageNumber);
        };
        $scope.restore = function(record) {
            srvEnrollRecord.restore(record);
        };

        $scope.page = {}; // 分页条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        srvEnrollApp.get().then(function(app) {
            srvEnrollRecord.init(app, $scope.page, {}, $scope.records);
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.dataSchemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            app._schemasFromEnrollApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    enrollDataSchemas.push(schema);
                }
            });
            $scope.enrollDataSchemas = enrollDataSchemas;
            app._schemasFromGroupApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    groupDataSchemas.push(schema);
                }
            });
            $scope.groupDataSchemas = groupDataSchemas;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
});
