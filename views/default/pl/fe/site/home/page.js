define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
        var catelogs = $scope.$root.catelogs;
        catelogs.splice(0, catelogs.length);
        $scope.$root.catelog = null;
        $scope.editPage = function(page) {
            var prop = page + '_page_name',
                name = $scope.site[prop];
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + name;
            } else {
                http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.site.id + '&page=' + page, function(rsp) {
                    $scope.site[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.resetPage = function(page) {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                var name = $scope.site[page + '_page_name'];
                if (name && name.length) {
                    http2.get('/rest/pl/fe/site/pageReset?site=' + $scope.site.id + '&page=' + page, function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + name;
                    });
                } else {
                    http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.site.id + '&page=' + page, function(rsp) {
                        $scope.site[prop] = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + rsp.data.name;
                    });
                }
            }
        };
        $scope.openPage = function(page) {
            var name = $scope.site[page + '_page_name'];
            if (name) {
                location.href = '/rest/site/home?site=' + $scope.site.id;
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
            mediagallery.open($scope.site.id, options);
        };
        $scope.remove = function(homeChannel, index) {
            slides.splice(index, 1);
            $scope.update('home_carousel');
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            if (!site.home_carousel) site.home_carousel = [];
            slides = site.home_carousel;
            $scope.slides = slides;
        });
    }]);
    ngApp.provider.controller('ctrlHomeChannel', ['$scope', 'http2', 'mattersgallery', function($scope, http2, mattersgallery) {
        $scope.add = function() {
            var options = {
                matterTypes: [{
                    value: 'channel',
                    title: '频道',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true
            };
            mattersgallery.open($scope.site.id, function(channels) {
                var channel;
                if (channels && channels.length) {
                    channel = channels[0];
                    http2.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + $scope.site.id, channel, function(rsp) {
                        $scope.channels.push(rsp.data);
                    });
                }
            }, options);
        };
        $scope.remove = function(homeChannel, index) {
            if (window.confirm('确定删除主页上的频道？')) {
                http2.get('/rest/pl/fe/site/setting/page/removeHomeChannel?site=' + $scope.site.id + '&id=' + homeChannel.id, function(rsp) {
                    $scope.channels.splice(index, 1);
                });
            }
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            http2.get('/rest/pl/fe/site/setting/page/listHomeChannel?site=' + site.id, function(rsp) {
                $scope.channels = rsp.data;
            });
        });
    }]);
});
