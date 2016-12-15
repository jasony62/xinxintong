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
        $scope.$watch('app', function(app) {
            if (!app) return;
            srvRecord.init(app, $scope.page, {}, $scope.records);
            // schemas
            srvApp.mapSchemas(app);
            $scope.enrollDataSchemas = app._schemasFromEnrollApp;
            $scope.groupDataSchemas = app._schemasFromGroupApp;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
});