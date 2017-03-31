ngApp.provider.controller('ctrlHome', ['$scope', '$http', '$uibModal', function($scope, $http, $uibModal) {
    function listSites() {
        $http.get('/rest/home/listSite').success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    }

    function listTemplates() {
        $http.get('/rest/home/listTemplate').success(function(rsp) {
            $scope.templates = rsp.data;
        });
    }
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    $scope.listApps = function() {
        $http.get('/rest/home/listApp').success(function(rsp) {
            $scope.apps = rsp.data.matters;
        });
    };
    $scope.listArticles = function() {
        $http.get('/rest/home/listArticle').success(function(rsp) {
            $scope.articles = rsp.data.matters;
        });
    };
    listSites();
    listTemplates();
    $scope.listApps();
    $scope.listArticles();
}]);
ngApp.provider.controller('ctrlCarousel', function($scope) {
    $scope.myInterval = 5000;
    $scope.noWrapSlides = false;
    $scope.active = 0;

    $scope.$watch('platform', function(platform) {
        if (platform === undefined) return;
        if (platform.home_carousel.length) {
            $scope.slides = platform.home_carousel;
        }
    });
});
