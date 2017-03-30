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
        $scope.$watch('platform', function(platform) {
            if (platform === undefined) return;
            if (!platform.home_carousel) platform.home_carousel = [];
            slides = platform.home_carousel;
            $scope.slides = slides;
        });
    }]);
});
