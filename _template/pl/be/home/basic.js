ngApp.provider.controller('ctrlHome', ['$scope', '$http', '$uibModal', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $http, $uibModal, tmsFavor, tmsForward, tmsDynaPage) {
    var page;
    $scope.page = page = {
        at: 1,
        size: 5,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    }

    function listSites(number) {
        $http.get('/rest/home/listSite' + '?page=1' + '&size=' + number).success(function(rsp) {
            $scope.sites = rsp.data.sites;
            $scope.sites.total = rsp.data.total;
        });
    }

    function listTemplates() {
        $http.get('/rest/home/listTemplate').success(function(rsp) {
            $scope.templates = rsp.data;
        });
    }
    $scope.moreMatters = function(matterType) {
        $scope.page.size = $scope.page.size + 5;
        switch (matterType) {
            case 'article':
                $scope.listArticles($scope.page.size);
                break;
            case 'app':
                $scope.listSites($scope.page.size);
                break;
            case 'site':
                $scope.listApps($scope.page.size);
                break;
        }
    }
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    $scope.listApps = function(number) {
        $http.get('/rest/home/listApp' + '?page=1' + '&size=' + number).success(function(rsp) {
            $scope.apps = rsp.data.matters;
            $scope.apps.total = rsp.data.total;
        });
    };
    $scope.listArticles = function(number) {
        $http.get('/rest/home/listArticle' + '?page=1' + '&size=' + number).success(function(rsp) {
            $scope.articles = rsp.data.matters;
            $scope.articles.total = rsp.data.total;
        });
    };
    $scope.favor = function(user, article) {
        article.type = article.matter_type;
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
    $scope.forward = function(user, article) {
        article.type = article.matter_type;
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
    $scope.listChannels = function() {
        $scope.channelArticles = [];
        $http.get('/rest/home/listChannel').success(function(rsp) {
            $scope.channels = rsp.data.matters;
            $scope.channels.forEach(function(item) {
                var url;
                url = '/rest/site/fe/matter/channel/mattersGet';
                url += '?site=' + item.siteid + '&id=' + item.matter_id;
                url += '&page=1&size=5';
                $http.get(url).success(function(rsp) {
                    $scope.channelArticles.push({title: item.title,url: item.url,data:rsp.data});
                });
            });
        });
    };
    $http.get('/rest/home/listMatterTop?type=article&page=1&size=3').success(function(rsp) {
        $scope.topArticles = rsp.data.matters;
    });
    document.querySelector('#gototop').addEventListener('click', function() {
        document.querySelector('body').scrollTop = 0;
    });
    listSites(5);
    listTemplates();
    $scope.listApps(5);
    $scope.listArticles(5);
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
