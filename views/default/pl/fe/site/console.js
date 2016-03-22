var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', function($lp) {
    $lp.html5Mode(true);
}]);
app.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
app.controller('ctrlConsole', ['$scope', 'http2', function($scope, http2) {
    $scope.open = function(matter) {
        if (matter.matter_type === 'article') {
            location.href = '/rest/pl/fe/matter/article?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'enroll') {
            location.href = '/rest/pl/fe/matter/enroll?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'mission') {
            location.href = '/rest/pl/fe/matter/mission/setting?id=' + matter.matter_id + '&site=' + $scope.siteId;
        }
    };
    $scope.addArticle = function() {
        http2.get('/rest/mp/matter/article/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/matter/article?id=' + rsp.data;
        });
    };
    $scope.addEnroll = function() {
        var url;
        url = '/rest/mp/app/enroll/create?mpid=' + $scope.id;
        http2.post(url, {}, function(rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.addTask = function() {
        http2.get('/rest/mp/mission/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/mission/setting?id=' + rsp.data.id;
        });
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId, function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);