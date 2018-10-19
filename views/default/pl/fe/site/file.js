ngApp = angular.module('app', ['ngRoute', 'ui.tms']);
ngApp.config(['$routeProvider', '$locationProvider', function($rp, $lp) {
    $rp.when('/rest/pl/fe/matter/news', {
        templateUrl: '/views/default/pl/fe/site/file/image.html?_=1',
        controller: 'ctrlImage',
    }).otherwise({
        templateUrl: '/views/default/pl/fe/site/file/image.html?_=1',
        controller: 'ctrlImage'
    });
    $lp.html5Mode(true);
}]);
ngApp.controller('ctrlFile', ['$scope', '$location','http2', function($scope, $location,http2) {
    var ls = $location.search();
    $scope.id = ls.id;
    $scope.siteId = ls.site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId).then(function(rsp) {
        $scope.site = rsp.data;
    });
}]);
ngApp.controller('ctrlImage', ['$scope', function($scope) {
    $scope.url = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.siteId;
    window.kcactSelectFile = function(url) {
        $scope.$apply(function() {
            $scope.mediaUrl = url;
        })
    };
}]);