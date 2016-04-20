define(["require", "angular", "angular-sanitize", "enroll-directive", "enroll-common"], function(require, angular) {
    'use strict';
    ngApp.controller('ctrlSignin', ['$scope', '$http', function($scope, $http) {
        $scope.data = {
            member: {}
        };
        $scope.signin = function() {
            $http.post(LS.j('signin/do', 'site', 'app'), $scope.data).success(function() {

            });
        };
        $scope.$on('xxt.app.enroll.ready', function(event, params) {
            if (params.record) {
                var schema, mapSchema, p, dataOfRecord, value;
                schema = JSON.parse(params.app.data_schemas);
                mapSchema = {};
                angular.forEach(schema, function(def) {
                    mapSchema[def.id] = def;
                });
                dataOfRecord = params.record.data;
                for (p in dataOfRecord) {
                    if (p === 'member') {
                        $scope.data.member = angular.extend($scope.data.member, dataOfRecord.member);
                    } else if (dataOfRecord[p].length) {
                        if (mapSchema[p].type === 'img') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = [];
                            for (var i in value) $scope.data[p].push({
                                imgSrc: value[i]
                            });
                        } else if (mapSchema[p].type === 'file') {
                            value = JSON.parse(dataOfRecord[p]);
                            $scope.data[p] = value;
                        } else if (mapSchema[p].type === 'multiple') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = {};
                            for (var i in value) $scope.data[p][value[i]] = true;
                        } else {
                            $scope.data[p] = dataOfRecord[p];
                        }
                    }
                }
                $scope.record = params.record;
            }
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});