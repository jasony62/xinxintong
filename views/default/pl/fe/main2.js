angular.module('app', ['ui.tms', 'ui.bootstrap']).
controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
    var url = '/rest/pl/fe/user/get?_=' + (new Date() * 1);
    http2.get(url, function(rsp) {
        $scope.loginUser = rsp.data;
    });
}]).controller('ctrlRecent', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.list = function() {
        var url = '/rest/pl/fe/recent';
        http2.get(url, function(rsp) {
            $scope.matters = rsp.data.matters;
        });
    };
    $scope.open = function(matter) {
        location.href = location.href = '/rest/pl/fe/matter/' + matter.matter_type + '?id=' + matter.matter_id + '&site=' + matter.siteid;
    };
    $scope.list();
}]).controller('ctrlSite', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.create = function() {
        var url = '/rest/pl/fe/site/create?_=' + t;;
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/site/setting?site=' + rsp.data.id;
        });
    };
    $scope.list = function() {
        var url = '/rest/pl/fe/site/list?_=' + t;
        http2.get(url, function(rsp) {
            $scope.sites = rsp.data;
        });
    };
    $scope.open = function(site) {
        location.href = '/rest/pl/fe/site?site=' + site.id + '&_=' + t;
    };
    $scope.list();
}]).controller('ctrlMission', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.open = function(mission) {
        location.href = '/rest/pl/fe/matter/mission?id=' + mission.mission_id;
    };
    $scope.list = function() {
        var url = '/rest/pl/fe/matter/mission/list?_=' + t;
        http2.get(url, function(rsp) {
            $scope.missions = rsp.data.missions;
        });
    };
    $scope.list();
}]).controller('ctrlTrend', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.list = function() {
        var url = '/rest/pl/fe/trends?_=' + t;
        http2.get(url, function(rsp) {
            $scope.trends = rsp.data.trends;
        });
    };
    $scope.list();
}]);