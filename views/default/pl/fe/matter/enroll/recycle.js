define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecycle', ['$scope', 'srvApp', 'srvRecord', function($scope, srvApp, srvRecord) {
        $scope.doSearch = function(pageNumber) {
            $scope.rows = {
                allSelected: 'N',
                selected: {}
            };
            srvRecord.searchRecycle(pageNumber);
        };
        $scope.restore = function(record) {
            srvRecord.restore(record);
        };

        $scope.page = {}; // 分页条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        srvApp.get().then(function(app) {
            srvRecord.init(app, $scope.page, {}, $scope.records);
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.data_schemas.forEach(function(schema) {
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
