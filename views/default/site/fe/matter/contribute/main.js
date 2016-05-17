ngApp = angular.module('xxtApp', ['ngRoute', 'ui.tms']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlContribute', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    $scope.appId = $location.search().app;
    http2.get('/rest/site/fe/matter/contribute/entry/list?site=' + $scope.siteId + '&app=' + $scope.appId, function(rsp) {
        $scope.entries = rsp.data.entries;
    })
    $scope.initiate = function(entry) {
        var url = '/rest/site/fe/matter/contribute/initiate';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.review = function(entry) {
        var url = '/rest/site/fe/matter/contribute/review';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.typeset = function(entry) {
        var url = '/rest/site/fe/matter/contribute/typeset';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
}]);