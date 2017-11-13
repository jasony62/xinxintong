ngApp.provider.controller('ctrlHome', ['$scope', '$http', '$location', '$anchorScroll', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $http, $location, $anchorScroll, tmsFavor, tmsForward, tmsDynaPage) {
    var ls = location.search,
        siteId = ls.match(/site=([^&]*)/)[1],
        width = angular.element(window).width(),
        page;

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
    }

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
                    data.pageAt = $scope.page.at;
                    if (data.data.length > 0) {
                        data.data.forEach(function(matter, index1) {
                            if (matter.matter_cont_tag != '' && matter.matter_cont_tag != undefined) {
                                matter.matter_cont_tag.forEach(function(mTag, index2) {
                                    $scope.oTagsC.forEach(function(oTag) {
                                        if (oTag.id === mTag) {
                                            matter.matter_cont_tag[index2] = oTag;
                                        }
                                    });
                                });
                            }
                            if(Object.keys(matter).indexOf('pic')!==-1&&matter.pic==null) {
                                matter.src = matter.pic = '';
                            }else if(Object.keys(matter).indexOf('thumbnail')!==-1&&matter.thumbnail==null){
                                matter.src = matter.thumnail = '';
                            }else {
                                matter.src = matter.pic ? matter.pic : matter.thumbnail;
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
    $scope.elSiteCard = angular.element(document.querySelector('#home'));
    $scope.siteCardToggled = function(open) {
        var elDropdownMenu;
        if (open) {
            if (elDropdownMenu = document.querySelector('#home>.dropdown-menu')) {
                elDropdownMenu.style.left = 'auto';
                elDropdownMenu.style.right = $scope.elSiteCard[0].offsetLeft + 'px';
            }
        }
    };
    $scope.moreMatters = function(id) {
        $scope.cTotal[id].pageAt++;
        $scope.page.at = $scope.cTotal[id].pageAt;
        $http.get('/rest/site/fe/matter/channel/mattersGet?site=' + siteId + '&id=' + id + '&' + page.j()).success(function(rsp) {
            var matterData = $scope.cTotal[id].data.matters;
            rsp.data.matters.forEach(function(item) {
                if(Object.keys(item).indexOf('pic')!==-1&&item.pic==null) {
                    item.src = item.pic = '';
                }else if(Object.keys(item).indexOf('thumbnail')!==-1&&item.thumbnail==null){
                    item.src = item.thumnail = '';
                }else {
                    item.src = item.pic ? item.pic : item.thumbnail;
                }
                matterData.push(item);
            });
            $scope.cTotal[id].data = matterData;
            $scope.cTotal[id].total = rsp.data.length;
        });
    };
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
    $scope.gotoTop = function() {
        $location.hash("home");
        $anchorScroll();
    };
    $scope.slideOnload = function(index) {
        if (index === 0) {
            _loadAll();
        }
    };
    function _loadAll() {
        tagMatters();
        listTemplates();
        c_listChannels();
        r_listChannels();
    }
    $scope.$watch('site', function(oSite) {
        var qrcodePic;
        if (oSite) {
            qrcodePic = '/rest/pl/fe/site/qrcode?site=' + oSite.id + '&url=' + encodeURIComponent(oSite.homeUrl);
            if (!oSite.home_qrcode_group) {
                oSite.home_qrcode_group = [];
            }
            oSite.home_qrcode_group.splice(0, 0, { picUrl: qrcodePic, tip: '团队首页二维码' });
            if (!oSite.home_carousel || oSite.home_carousel.length === 0) {
                _loadAll();
            }
        }
    });
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