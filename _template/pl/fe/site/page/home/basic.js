ngApp.provider.controller('ctrlHome', ['$scope', '$http', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $http, tmsFavor, tmsForward, tmsDynaPage) {
    var ls = location.search,
        siteId = ls.match(/site=([^&]*)/)[1];
    $scope.siteId = siteId;

    function listTemplates() {
        $http.get('/rest/site/home/listTemplate?site=' + siteId).success(function(rsp) {
            $scope.templates = rsp.data;
        });
    };

    function listChannels() {
        $http.get('/rest/site/home/listChannel?site=' + siteId + '&homeGroup=C').success(function(rsp) {
            $scope.c_channels = rsp.data;
            $scope.c_channels.forEach(function(channel) {
                $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + channel.channel_id).success(function(rsp) {
                    channel._matters = rsp.data;
                });
            });
        });
        $http.get('/rest/site/home/listChannel?site=' + siteId + '&homeGroup=R').success(function(rsp) {
            $scope.r_channels = rsp.data;
            $scope.r_channels.forEach(function(channel) {
                $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + channel.channel_id).success(function(rsp) {
                    channel._matters = rsp.data;
                });
            });
        });
    };
    $scope.favor = function(user,article) {
        event.preventDefault();
        event.stopPropagation();

        if (!user.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + oMatter.siteid).then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsFavor.open(article);
            });
        } else {
            tmsFavor.open(article);
        }
    }
    $scope.forward = function(user,article) {
        event.preventDefault();
        event.stopPropagation();

        if (!user.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + oMatter.siteid).then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsForward.open(article);
            });
        } else {
            tmsForward.open(article);
        }
    }
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
