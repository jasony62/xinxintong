define(["require", "angular", "angular-sanitize", "xxt-image", "xxt-geo", "enroll-directive", "enroll-common"], function(require, angular) {
    'use strict';
    ngApp.factory('Record', ['$http', '$q', function($http, $q) {
        var Record, _ins;
        Record = function() {};
        Record.prototype.get = function(ek) {
            var url, deferred;
            deferred = $q.defer();
            deferred.resolve({
                data: {}
            });
            return deferred.promise;
        };
        return {
            ins: function() {
                return _ins ? _ins : (new Record());
            }
        };
    }]);
    ngApp.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
        var facRecord = Record.ins(),
            schemas = $scope.app.data_schemas;
        $scope.value2Label = function(key) {
            var val, i, j, s, aVal, aLab = [];
            if (schemas && facRecord.current && facRecord.current.data) {
                val = facRecord.current.data[key];
                if (val === undefined) return '';
                for (i = 0, j = schemas.length; i < j; i++) {
                    s = schemas[i];
                    if (schemas[i].id === key) {
                        s = schemas[i];
                        break;
                    }
                }
                if (s && s.ops && s.ops.length) {
                    aVal = val.split(',');
                    for (i = 0, j = s.ops.length; i < j; i++) {
                        aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
                    }
                    if (aLab.length) return aLab.join(',');
                }
                return val;
            } else {
                return '';
            }
        };
        $scope.editRecord = function(event, page) {};
        $scope.gotoEnroll = function(event, page) {};
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    }]);
    ngApp.directive('tmsImageInput', function($compile, $q) {
        return {
            restrict: 'A',
            controller: function($scope, $timeout) {}
        }
    });
    ngApp.directive('tmsFileInput', function($q) {
        return {
            restrict: 'A',
            controller: function($scope, $timeout) {}
        }
    });
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});