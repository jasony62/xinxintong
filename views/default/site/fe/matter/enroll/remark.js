define(["angular", "enroll-common", "angular-sanitize", "xxt-share"], function(angular, ngApp) {
    'use strict';
    ngApp.controller('ctrlRemark', ['$scope', '$q', '$http', function($scope, $q, $http) {
        function listRemarks(schema) {
            var url, defer = $q.defer();
            url = '/rest/site/fe/matter/enroll/remark/list?site=' + oApp.siteid + '&ek=' + ek;
            schema && (url += '&schema=' + schema.id);
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data)
            });
            return defer.promise;
        }

        function summary() {
            var url, defer = $q.defer();
            url = '/rest/site/fe/matter/enroll/remark/summary?site=' + oApp.siteid + '&ek=' + ek;
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data)
            });
            return defer.promise;
        }
        var oApp, ek, schemaRemarks, remarkableSchemas = [];
        ek = location.search.match(/[\?&]ek=([^&]*)/)[1];
        $scope.newRemark = {};
        $scope.schemaRemarks = schemaRemarks = {};
        $scope.switchSchema = function(schema) {
            if (schema._open) {
                listRemarks(schema).then(function(result) {
                    schemaRemarks[schema.id] = result.remarks;
                })
            }
        };
        $scope.addRemark = function(schema) {
            var url;
            url = '/rest/site/fe/matter/enroll/remark/add?site=' + oApp.siteid + '&ek=' + ek;
            schema && (url += '&schema=' + schema.id);
            $http.post(url, $scope.newRemark).success(function(rsp) {
                if (schema) {
                    !schemaRemarks[schema.id] && (schemaRemarks[schema.id] = []);
                    schemaRemarks[schema.id].splice(0, 0, remark);
                } else {
                    $scope.remarks.splice(0, 0, rsp.data);
                }
                $scope.newRemark.content = '';
            });
        };
        $scope.$on('xxt.app.enroll.ready', function(event, params) {
            oApp = params.app;
            summary().then(function(result) {
                var summaryBySchema = {};
                result.forEach(function(schema) {
                    summaryBySchema[schema.schema_id] = schema;
                });
                oApp.data_schemas.forEach(function(schema) {
                    summaryBySchema[schema.id] && (schema.summary = summaryBySchema[schema.id]);
                    remarkableSchemas.push(schema);
                });
                $scope.remarkableSchemas = remarkableSchemas;
            });
            listRemarks().then(function(result) {
                $scope.remarks = result.remarks;
            });
        });
    }]);

    angular._lazyLoadModule('enroll');

    return ngApp;
});
