xxtApp = angular.module('xxtApp', ['ui.tms']);
xxtApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
xxtApp.controller('contributeCtrl', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            $scope.mpid = params.mpid;
            $scope.entries = params.entries;
        }
    });
    $scope.initiate = function(entry) {
        var url = '/rest/app/contribute/initiate';
        url += '?mpid=' + $scope.mpid;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.review = function(entry) {
        var url = '/rest/app/contribute/review';
        url += '?mpid=' + $scope.mpid;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.typeset = function(entry) {
        var url = '/rest/app/contribute/typeset';
        url += '?mpid=' + $scope.mpid;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
}]);