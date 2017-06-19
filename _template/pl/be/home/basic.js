ngApp.provider.controller('ctrlHome', ['$scope', '$http', '$uibModal', 'tmsFavor', 'tmsForward', 'tmsDynaPage', function($scope, $http, $uibModal, tmsFavor, tmsForward, tmsDynaPage) {
    var page, goTop, width = angular.element(window).width();
    $scope.width = width;
    width > 768 ? goTop = document.querySelector('#md_gototop') : goTop = document.querySelector('#xs_gototop');
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
    $scope.listChannels = function() {
        $scope.channelArticles = [], $scope.h_prev_channels = [], $scope.h_next_channels = [];
        $http.get('/rest/home/listChannel').success(function(rsp) {
            $scope.channels = rsp.data.matters;
            rsp.data.matters.forEach(function(item, index) {
                index < 3 ? $scope.h_prev_channels.push(item) : $scope.h_next_channels.push(item);
            });
            width > 768 ? $scope.h_channels_matters = $scope.channels : $scope.h_channels_matters = $scope.h_prev_channels;
            $scope.h_channels_matters.forEach(function(item, index) {
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
    goTop.addEventListener('click', function() {
        document.querySelector('body').scrollTop = 0;
    });
    listSites(5);
    listTemplates();
    $scope.listApps(12);
    $scope.listArticles(12);
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
ngApp.provider.controller('ctrlSlider',function($scope) {
    var meuns = angular.element('#arrow').find('a'),
        lis = document.querySelector('#slider_extends > ul').children,
        as = [meuns[0],meuns[1]];
    var stop = true, flag = true;
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
    function animate(obj,json,fn) {
        clearInterval(obj.timer);
        obj.timer = setInterval(function() {
            var flag = true;
            for(var attr in json){
                var current = 0;
                if(attr == "opacity") {
                    current = Math.round(parseInt(getStyle(obj,attr)*100)) || 0;
                } else {
                    current = parseInt(getStyle(obj,attr));
                }
                // 目标位置就是  属性值
                var step = ( json[attr] - current) / 10;
                step = step > 0 ? Math.ceil(step) : Math.floor(step);
                //判断透明度
                if(attr == "opacity") {
                    if("opacity" in obj.style) {
                        obj.style.opacity = (current + step) /100;
                    } else {
                        obj.style.filter = "alpha(opacity = "+(current + step)* 10+")";
                    }
                } else if (attr == "zIndex") {
                    obj.style.zIndex = json[attr];
                } else {
                    obj.style[attr] = current  + step + "px" ;
                }

                if(current != json[attr]) {
                    flag =  false;
                }
            }
            if(flag) {
                clearInterval(obj.timer);
                if(fn) { fn();}
            }
        },30)
    }
    function getStyle(obj,attr) {
        if(obj.currentStyle) {
            return obj.currentStyle[attr];
        } else {
            return window.getComputedStyle(obj,null)[attr];
        }
    }
    $scope.load = function() {
        if($scope.width  < 768) {change();}
    }
});