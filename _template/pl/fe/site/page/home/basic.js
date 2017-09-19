ngApp.provider.controller('ctrlHome', ['$scope', '$http', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $http, tmsFavor, tmsForward, tmsDynaPage) {
    var ls = location.search,
        siteId = ls.match(/site=([^&]*)/)[1],
        width = angular.element(window).width(),
        page, entry, url, goTop;
    width > 768 ? goTop = document.querySelector('#md_gototop') : goTop = document.querySelector('#xs_gototop');
    url = 'http://' + location.host + '/rest/site/home?site=' + siteId;
    $scope.entry = entry = {
        url: url,
        qrcode: '/rest/pl/fe/site/qrcode?site=' + siteId + '&url=' + encodeURIComponent(url)
    }
    $scope.cTotal = [];
    $scope.siteId = siteId;
    $scope.page = page = {
        at: 1,
        size: 12,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    }

    function listTemplates() {
        $http.get('/rest/site/home/listTemplate?site=' + siteId).success(function(rsp) {
            $scope.templates = rsp.data;
        });
    }

    function tagMatters() {
        $http.get('/rest/pl/fe/matter/tag/listTags?site=' + siteId + '&subType=C').success(function(rsp) {
            $scope.oTagsC = rsp.data;
        });
    };
    $scope.moreMatters = function(id) {
        $scope.cTotal[id].pageAt++;
        $scope.page.at = $scope.cTotal[id].pageAt;
        $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + id + '&' + page.j()).success(function(rsp) {
            var matterData = $scope.cTotal[id].data.matters;
            rsp.data.matters.forEach(function(item) {
                matterData.push(item);
            });
            $scope.cTotal[id].data = matterData;
            $scope.cTotal[id].total = rsp.data.length;
        });
    };

    function c_listChannels() {
        $scope.c_prev_channels = [], $scope.c_next_channels = [];
        $http.get('/rest/site/home/listChannel?site=' + siteId + '&homeGroup=C').success(function(rsp) {
            $scope.c_channels = rsp.data;
            rsp.data.forEach(function(item, index) {
                index < 3 ? $scope.c_prev_channels.push(item) : $scope.c_next_channels.push(item);
            });
            $scope.c_channels_matters = $scope.c_prev_channels;
            $scope.c_channels_matters.forEach(function(channel) {
                $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + channel.channel_id + '&' + page.j()).success(function(rsp) {
                    var chid = channel.channel_id,
                        data = [];
                    data.data = rsp.data.matters;
                    data.total = rsp.data.length;
                    data.pageAt = $scope.page.at;
                    if(data.total > 0){
                        data.data.forEach(function(matter, index1) {
                            if(matter.matter_cont_tag != '' && matter.matter_cont_tag != undefined){
                                matter.matter_cont_tag.forEach(function(mTag, index2) {
                                    $scope.oTagsC.forEach(function(oTag) {
                                        if (oTag.id === mTag) {
                                            matter.matter_cont_tag[index2] = oTag;
                                        }
                                    });
                                });
                            }
                        });
                    }
                    $scope.cTotal[chid] = data;
                });
            });
        });
    }

    function r_listChannels() {
        $scope.r_prev_channels = [], $scope.r_next_channels = [], $scope.channelArticles = [];
        $http.get('/rest/site/home/listChannel?site=' + siteId + '&homeGroup=R').success(function(rsp) {
            $scope.r_channels = rsp.data;
            rsp.data.forEach(function(item, index) {
                index < 3 ? $scope.r_prev_channels.push(item) : $scope.r_next_channels.push(item);
            });
            width > 768 ? $scope.r_channels_matters = $scope.r_channels : $scope.r_channels_matters = $scope.r_channels_matters = $scope.r_prev_channels;
            $scope.r_channels_matters.forEach(function(item, index) {
                $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + item.channel_id + '&page=1&size=5').success(function(rsp) {
                    $scope.channelArticles.push({
                        title: item.title,
                        url: '/rest/site/fe/matter?site=' + item.siteid + '&id=' + item.channel_id + '&type=channel',
                        data: rsp.data.matters
                    });
                });
            });
        });
    }
    $scope.favor = function(user, article) {
        event.preventDefault();
        event.stopPropagation();

        if (!user.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + siteId).then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsFavor.open(article);
            });
        } else {
            tmsFavor.open(article);
        }
    };
    $scope.forward = function(user, article) {
        event.preventDefault();
        event.stopPropagation();

        if (!user.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + siteId).then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsForward.open(article);
            });
        } else {
            tmsForward.open(article);
        }
    };
    $scope.openMatter = function(matter) {
        location.href = matter.url;
    };
    goTop.addEventListener('click', function() {
        document.querySelector('body').scrollTop = 0;
    });
    tagMatters();
    listTemplates();
    c_listChannels();
    r_listChannels();
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
