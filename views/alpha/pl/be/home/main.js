define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        function createPage(pageType) {
            var url = '/rest/pl/be/home/pageCreate?name=' + pageType;
            url += '&template=basic';
            http2.get(url, function(rsp) {
                $scope.platform[pageType + '_page_name'] = rsp.data.name;
                location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
            });
        }

        function resetPage(pageType) {
            var name = $scope.platform[pageType + '_page_name'],
                url;
            if (name && name.length) {
                url = '/rest/pl/be/home/pageReset?name=' + pageType;
                url += '&template=basic';
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe/code?site=platform&name=' + name;
                });
            } else {
                url = '/rest/pl/be/home/pageCreate?name=' + pageType;
                url += '&template=basic';
                http2.get(url, function(rsp) {
                    $scope.platform[pageType + '_page_name'] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
                });
            }
        }

        $scope.editPage = function(pageType) {
            var name = $scope.platform[pageType + '_page_name'];
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=platform&name=' + name;
            } else {
                createPage(pageType);
            }
        };
        $scope.resetPage = function(pageType) {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                resetPage(pageType);
            }
        };
    }]);
    ngApp.provider.controller('ctrlHomeCarousel', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        var slides;
        $scope.add = function() {
            var options = {
                callback: function(url) {
                    slides.push({
                        picUrl: url + '?_=' + (new Date() * 1)
                    });
                    $scope.update('home_carousel');
                }
            };
            mediagallery.open('platform', options);
        };
        $scope.remove = function(homeChannel, index) {
            slides.splice(index, 1);
            $scope.update('home_carousel');
        };
        $scope.up = function(slide, index) {
            if (index === 0) return;
            slides.splice(index, 1);
            slides.splice(--index, 0, slide);
            $scope.update('home_carousel');
        };
        $scope.down = function(slide, index) {
            if (index === slides.length - 1) return;
            slides.splice(index, 1);
            slides.splice(++index, 0, slide);
            $scope.update('home_carousel');
        };
        $scope.$watch('platform', function(platform) {
            if (platform === undefined) return;
            if (!platform.home_carousel) platform.home_carousel = [];
            slides = platform.home_carousel;
            $scope.slides = slides;
        });
    }]);
    ngApp.provider.controller('ctrlHomeNav', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        var navs;
        $scope.add = function() {
            $uibModal.open({
                templateUrl: 'navSites.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var page, selected = [];
                    $scope2.page = page = {};
                    $scope2.listSite = function() {
                        var url = '/rest/pl/be/home/recommend/listSite';
                        http2.get(url, function(rsp) {
                            $scope2.sites = rsp.data.sites;
                            $scope2.page.total = rsp.data.total;
                        });
                    };
                    $scope2.choose = function(site) {
                        if (site._selected === 'Y') {
                            selected.push(site);
                        } else {
                            selected.splice(selected.indexOf(site), 1);
                        }
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close(selected);
                    };
                    $scope2.listSite();
                }],
                backdrop: 'static'
            }).result.then(function(sites) {
                if (sites && sites.length) {
                    sites.forEach(function(site) {
                        navs.push({ title: site.title, site: { id: site.siteid, name: site.title }, type: 'site' });
                    });
                    $scope.update('home_nav');
                }
            });
        };
        $scope.edit = function(homeNav) {
            $uibModal.open({
                templateUrl: 'editNavSite.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.homeNav = angular.copy(homeNav);
                    $scope2.ok = function() {
                        $mi.close($scope2.homeNav);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(newHomeNav) {
                homeNav.title = newHomeNav.title;
                $scope.update('home_nav');
            });
        };
        $scope.remove = function(homeNav, index) {
            navs.splice(index, 1);
            $scope.update('home_nav');
        };
        $scope.up = function(homeNav, index) {
            if (index === 0) return;
            navs.splice(index, 1);
            navs.splice(--index, 0, homeNav);
            $scope.update('home_nav');
        };
        $scope.down = function(homeNav, index) {
            if (index === navs.length - 1) return;
            navs.splice(index, 1);
            navs.splice(++index, 0, homeNav);
            $scope.update('home_nav');
        };
        $scope.$watch('platform', function(platform) {
            if (platform === undefined) return;
            if (!platform.home_nav) platform.home_nav = [];
            $scope.navs = navs = platform.home_nav;
        });
    }]);
    ngApp.provider.controller('ctrlHomeQrcode', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        var qrcodes;
        $scope.add = function() {
            var options = {
                callback: function(url) {
                    qrcodes.push({
                        picUrl: url + '?_=' + (new Date() * 1)
                    });
                    $scope.update('home_qrcode_group');
                }
            };
            mediagallery.open('platform', options);
        };
        $scope.doTip = function(tip) {
            $scope.update('home_qrcode_group');
        }
        $scope.remove = function(slide, index) {
            qrcodes.splice(index, 1);
            $scope.update('home_qrcode_group');
        };
        $scope.up = function(slide, index) {
            if (index === 0) return;
            qrcodes.splice(index, 1);
            qrcodes.splice(--index, 0, slide);
            $scope.update('home_qrcode_group');
        };
        $scope.down = function(slide, index) {
            if (index === qrcodes.length - 1) return;
            qrcodes.splice(index, 1);
            qrcodes.splice(++index, 0, slide);
            $scope.update('home_qrcode_group');
        };
        $scope.$watch('platform', function(platform) {
            if (platform === undefined) return;
            if (!platform.home_qrcode_group) platform.home_qrcode_group = [];
            qrcodes = platform.home_qrcode_group;
            $scope.qrcodes = qrcodes;
        });
    }]);
});
