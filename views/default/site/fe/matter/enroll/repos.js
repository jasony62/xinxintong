'use strict';
require('!style-loader!css-loader!./remark.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['$http', '$q', function($http, $q) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
    };
    Round.prototype.list = function() {
        var deferred, url;
        deferred = $q.defer();
        url = '/rest/site/fe/matter/enroll/round/list?site=' + this.oApp.siteid + '&app=' + this.oApp.id;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function(oApp) {
            _ins = _ins ? _ins : new Round(oApp);
            return _ins;
        }
    };
}]);
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
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable === 'Y') {
                schema._open = false;
                schemas.push(schema);
            }
        });
        schemas = angular.copy(schemas);
    });
}]);
