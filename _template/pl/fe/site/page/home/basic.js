ngApp.provider.controller('ctrlHome', ['$scope', '$http', function($scope, $http) {
    var ls = location.search,
        siteId = ls.match(/site=([^&]*)/)[1];

    function listTemplates() {
        $http.get('/rest/site/home/listTemplate?site=' + siteId).success(function(rsp) {
            $scope.templates = rsp.data;
        });
    };

    function listChannels() {
        $http.get('/rest/site/home/listChannel?site=' + siteId).success(function(rsp) {
            $scope.channels = rsp.data;
            $scope.channels.forEach(function(channel) {
                $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + channel.channel_id).success(function(rsp) {
                    channel._matters = rsp.data;
                });
            });
        });
    };
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    listTemplates();
    listChannels();
}]);
ngApp.provider.controller('ctrlCarousel', function($scope) {
    $scope.myInterval = 5000;
    $scope.noWrapSlides = false;
    $scope.active = 0;

    $scope.$watch('site', function(site) {
        if (site === undefined) return;
        if (site.home_carousel.length) {
            $scope.slides = site.home_carousel;
        }
    });
});
