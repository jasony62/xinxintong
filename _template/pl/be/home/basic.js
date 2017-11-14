ngApp.provider.controller('ctrlHome', ['$scope', '$q', '$http', '$location', '$anchorScroll', '$uibModal', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $q, $http, $location, $anchorScroll, $uibModal, tmsFavor, tmsForward, tmsDynaPage) {
    var _oPage, width = angular.element(window).width();
    $scope.width = width;
    $scope.page = _oPage = {
        at: 1,
        size: 10,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    }
    $scope.moreMatters = function(matterType, matter) {
        _oPage.at++;
        switch (matterType) {
            case 'site':
                listSites();
                break;
            case 'app':
                $scope.listApps();
                break;
            case 'article':
                $scope.listMatters();
                break;
            case 'channel':
                $scope.listChannelsMatters(matter);
                break;
        }
    };
    $scope.openMatter = function(matter) {
        location.href = matter.type!==undefined ? matter.url : '/rest/site/home?site=' + matter.siteid;
    };
    function dealImgSrc(item) {
        if(Object.keys(item).indexOf('pic')!==-1&&item.pic==null) {
            item.src = item.pic = '';
        }else if(Object.keys(item).indexOf('thumbnail')!==-1&&item.thumbnail==null){
            item.src = item.thumnail = '';
        }else {
            item.src = item.pic ? item.pic : item.thumbnail;
        }
        return item;
    }
    var _templates = [];
    function listTemplates() {
        $http.get('/rest/home/listTemplate').success(function(rsp) {
            if(rsp.data.length) {
                rsp.data.forEach(function(data) {
                    dealImgSrc(data);
                    _templates.push(data);
                });
            }
            $scope.templates = _templates;
        });
    };
    var _sites = [];
    function listSites() {
        $http.get('/rest/home/listSite?' + _oPage.j()).success(function(rsp) {
            if (rsp.data.sites.length) {
                rsp.data.sites.forEach(function(item) {
                    dealImgSrc(item);
                    _sites.push(item);
                });
                $scope.sites = _sites;
                $scope.sites.total = rsp.data.total;
            }
        });
    };
    var _apps = [];
    $scope.listApps = function() {
        $http.get('/rest/home/listApp?' + _oPage.j()).success(function(rsp) {
            if (rsp.data.matters.length) {
                rsp.data.matters.forEach(function(item) {
                    _apps.push(item);
                });
                $scope.apps = _apps;
                $scope.apps.total = rsp.data.total;
            }
        });
    };
    var _matters = [];
    $scope.listMatters = function() {
        $http.get('/rest/home/listMatter?' + _oPage.j()).success(function(rsp) {
            if (rsp.data.matters.length) {
                rsp.data.matters.forEach(function(item) {
                    dealImgSrc(item);
                    _matters.push(item);
                });
                $scope.matters = _matters;
                $scope.matters.total = rsp.data.total;
            }
        });
    };
    var _channelMatters = [];
    $scope.listChannels1 = function() {
        $http.get('/rest/home/listChannel?homeGroup=c').success(function(rsp) {
            $scope.channels1 = rsp.data.matters;
            if (rsp.data.matters.length) {
                rsp.data.matters.forEach(function(item) {
                    $scope.listChannelsMatters(item);
                });
            }
        });
    };
    $scope.listChannelsMatters = function(item) {
        var url;
        url = '/rest/site/fe/matter/channel/mattersGet';
        url += '?site=' + item.siteid + '&id=' + item.matter_id;
        url += '&' + _oPage.j();
        $http.get(url).success(function(rsp) {
            rsp.data.matters.forEach(function(matter) {
                dealImgSrc(matter);
            });
            if (_oPage.at == 1) {
                _channelMatters.push({ title: item.title, siteid: item.siteid, matter_id: item.matter_id, data: rsp.data.matters, total: rsp.data.total });
            }
            if ($scope.page.at > 1) {
                if (rsp.data.matters.length) {
                    _channelMatters.forEach(function(channel) {
                        if (channel.matter_id == item.matter_id) {
                            rsp.data.matters.forEach(function(matter) {
                                channel.data.push(matter);
                            })
                        }
                    });
                }
            }
            $scope.channelMatters = _channelMatters;
        });
    };
    $scope.listChannels2 = function() {
        $scope.channelArticles = [], $scope.h_prev_channels = [], $scope.h_next_channels = [];
        $http.get('/rest/home/listChannel?homeGroup=r').success(function(rsp) {
            $scope.channels2 = rsp.data.matters;
            rsp.data.matters.forEach(function(item, index) {
                index < 3 ? $scope.h_prev_channels.push(item) : $scope.h_next_channels.push(item);
            });
            width > 768 ? $scope.h_channels_matters = $scope.channels2 : $scope.h_channels_matters = $scope.h_prev_channels;
            $scope.h_channels_matters.forEach(function(item, index) {
                var url;
                url = '/rest/site/fe/matter/channel/mattersGet';
                url += '?site=' + item.siteid + '&id=' + item.matter_id;
                url += '&page=1&size=5';
                $http.get(url).success(function(rsp) {
                    $scope.channelArticles.push({ title: item.title, url: item.url, data: rsp.data.matters });
                });
            });
        });
    };
    $scope.favor = function(user, article) {
        article.type = article.matter_type;
        event.preventDefault();
        event.stopPropagation();

        if (!user.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + article.siteid).then(function(data) {
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
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + article.siteid).then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsForward.open(article);
            });
        } else {
            tmsForward.open(article);
        }
    }
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
        listSites();
        listTemplates();
        $scope.listApps();
        $scope.listMatters();
        $scope.listChannels1();
        $scope.listChannels2();
    }
    $scope.$watch('platform', function(platform) {
        if (platform === undefined) return;
        $http.get('/rest/home/listMatterTop?page=1&size=3').success(function(rsp) {
            $scope.topArticles = rsp.data.matters;
        });
        if (!platform.home_carousel || platform.home_carousel.length === 0) {
            _loadAll();
        }
    });
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
ngApp.provider.controller('ctrlSlider', function($scope) {
    var meuns = angular.element('#arrow').find('a'),
        lis = document.querySelector('#slider_extends > ul').children,
        as = [meuns[0], meuns[1]];
    var stop = true,
        flag = true;
    var json = [{
        width: 169,
        top: 40,
        left: -113,
        opacity: 80,
        z: 3
    }, {
        width: 225,
        top: 16,
        left: 75,
        opacity: 100,
        z: 5
    }, {
        width: 169,
        top: 40,
        left: 319,
        opacity: 80,
        z: 3
    }];
    for (var k in as) {
        as[k].onclick = function() {
            if (this.className == "prev") {
                if (stop == true) {
                    change(false);
                    stop = false;
                }
            } else {
                if (stop == true) {
                    change(true);
                    stop = false;
                }
            }
        }
    }

    function change(flag) {
        if (flag) {
            json.unshift(json.pop());
        } else {
            json.push(json.shift());
        }
        for (var i = 0; i < lis.length; i++) {
            animate(lis[i], {
                width: json[i].width,
                top: json[i].top,
                left: json[i].left,
                opacity: json[i].opacity,
                zIndex: json[i].z
            }, function() {
                stop = true;
            })
        }
    }

    function animate(obj, json, fn) {
        clearInterval(obj.timer);
        obj.timer = setInterval(function() {
            var flag = true;
            for (var attr in json) {
                var current = 0;
                if (attr == "opacity") {
                    current = Math.round(parseInt(getStyle(obj, attr) * 100)) || 0;
                } else {
                    current = parseInt(getStyle(obj, attr));
                }
                // 目标位置就是  属性值
                var step = (json[attr] - current) / 10;
                step = step > 0 ? Math.ceil(step) : Math.floor(step);
                //判断透明度
                if (attr == "opacity") {
                    if ("opacity" in obj.style) {
                        obj.style.opacity = (current + step) / 100;
                    } else {
                        obj.style.filter = "alpha(opacity = " + (current + step) * 10 + ")";
                    }
                } else if (attr == "zIndex") {
                    obj.style.zIndex = json[attr];
                } else {
                    obj.style[attr] = current + step + "px";
                }

                if (current != json[attr]) {
                    flag = false;
                }
            }
            if (flag) {
                clearInterval(obj.timer);
                if (fn) { fn(); }
            }
        }, 30)
    }

    function getStyle(obj, attr) {
        if (obj.currentStyle) {
            return obj.currentStyle[attr];
        } else {
            return window.getComputedStyle(obj, null)[attr];
        }
    }
    $scope.load = function() {
        if ($scope.width < 768) { change(); }
    }
});