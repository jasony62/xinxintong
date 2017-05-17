'use strict';
require('./remark.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlRemark', ['$scope', '$q', '$http', function($scope, $q, $http) {
    function listRemarks() {
        var url, defer = $q.defer();
        url = '/rest/site/fe/matter/enroll/remark/list?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + schemaId;
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
    var oApp, ek, schemaId;
    ek = location.search.match(/[\?&]ek=([^&]*)/)[1];
    schemaId = location.search.match(/[\?&]schema=([^&]*)/)[1];
    $scope.schemaId = schemaId;
    $scope.newRemark = {};
    $scope.addRemark = function() {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/add?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + schemaId;
        $http.post(url, $scope.newRemark).success(function(rsp) {
            $scope.remarks.splice(0, 0, rsp.data);
            $scope.newRemark.content = '';
        });
    };
    $scope.likeRemark = function(oRemark) {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/like';
        url += '?site=' + oApp.siteid;
        url += '&remark=' + oRemark.id;
        $http.get(url).success(function(rsp) {
            oRemark.like_log = rsp.data.like_log;
            oRemark.like_num = rsp.data.like_num;
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        $scope.record = params.record;
        listRemarks().then(function(data) {
            $scope.remarks = data.remarks;
        });
    });
}]);
