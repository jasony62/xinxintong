define(["angular", "enroll-common", "angular-sanitize", "xxt-share"], function(angular, ngApp) {
    'use strict';
    ngApp.controller('ctrlRepos', ['$scope', '$http', function($scope, $http) {
        var oApp, schemas = [];
        $scope.schemas = schemas;
        $scope.repos = {};
        $scope.list4Schema = function(schema, page) {
            var url;
            url = '/rest/site/fe/matter/enroll/repos/list4Schema?site=' + oApp.siteid + '&app=' + oApp.id;
            url += '&schema=' + schema.id;
            url += '&page=' + page.at + '&size=' + page.size;
            $http.get(url).success(function(result) {
                $scope.repos[schema.id] = result.data.records;
                page.total = result.data.total;
            });
        };
        $scope.switchSchema = function(schema, page) {
            schema._open = !schema._open;
            if (schema._open) {
                $scope.list4Schema(schema, page);
            }
        };
        $scope.gotoRemark = function(ek, page) {
            var url;
            url = '/rest/site/fe/matter/enroll?site=' + oApp.siteid + '&app=' + oApp.id + '&page=remark';
            url += '&ek=' + ek;
            location.href = url;
        };
        $scope.$on('xxt.app.enroll.ready', function(event, params) {
            oApp = params.app;
            params.app.data_schemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    schemas.push(schema);
                }
            });
            schemas = angular.copy(schemas);
        });
    }]);

    angular._lazyLoadModule('enroll');

    return ngApp;
});
