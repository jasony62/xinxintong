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
    $scope.listChannels = function() {
        $http.get('/rest/home/listChannel').success(function(rsp) {
            $scope.channels = rsp.data.matters;
            $scope.channels.forEach(function(item) {
                var url;
                    url = '/rest/site/fe/matter/channel/mattersGet';
                    url += '?site=' + item.siteid + '&id=' + item.matter_id;
                    url += '&page=1&size=5';
                $http.get(url).success(function(rsp) {
                    $scope.channelArticles = rsp.data;
                });
            });
        });
    };
    $http.get('/rest/home/listMatterTop?type=article&page=1&size=3').success(function(rsp) {
        $scope.topArticles = rsp.data.matters;
    });
    listSites();
    listTemplates();
    $scope.listApps();
    $scope.listArticles();
    $scope.listChannels();
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
