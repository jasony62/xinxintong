define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
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
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.site.name + '_主页二维码.png"></a>')[0].click();
        };
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/site/applyToHome?site=' + $scope.site.id;
            http2.get(url, function(rsp) {});
        };
        $scope.$watch('site', function(oSite) {
            if (!oSite) return;
            var entry, url;
            url = 'http://' + location.host + '/rest/site/home?site=' + oSite.id;
            entry = {
                url: url,
                qrcode: '/rest/pl/fe/site/qrcode?site=' + oSite.id + '&url=' + encodeURIComponent(url),
            };
            $scope.entry = entry;
        });
    }]);
    ngApp.provider.controller('ctrlHomeCarousel', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        function update(name) {
            var p = {},
                site = $scope.site;
            p[name] = site[name];
            http2.post('/rest/pl/fe/site/update?site=' + site.id, p, function(rsp) {});
        }
        var slides;
        $scope.add = function() {
            var options = {
                callback: function(url) {
                    slides.push({
                        picUrl: url + '?_=' + (new Date() * 1)
                    });
                    update('home_carousel');
                }
            };
            mediagallery.open($scope.site.id, options);
        };
        $scope.remove = function(slide, index) {
            slides.splice(index, 1);
            update('home_carousel');
        };
        $scope.up = function(slide, index) {
            if (index === 0) return;
            slides.splice(index, 1);
            slides.splice(--index, 0, slide);
            update('home_carousel');
        };
        $scope.down = function(slide, index) {
            if (index === slides.length - 1) return;
            slides.splice(index, 1);
            slides.splice(++index, 0, slide);
            update('home_carousel');
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            if (!site.home_carousel) site.home_carousel = [];
            slides = site.home_carousel;
            $scope.slides = slides;
        });
    }]);
    ngApp.provider.controller('ctrlHomeChannel', ['$scope', 'http2', 'mattersgallery', function($scope, http2, mattersgallery) {
        function updateSeq() {
            var updated = {};
            $scope.channels.forEach(function(channel, index) {
                updated[channel.id] = index;
            });
            http2.post('/rest/pl/fe/site/setting/page/seqHomeChannel?site=' + $scope.site.id, updated, function(rsp) {});
        }
        $scope.create = function() {
            http2.get('/rest/pl/fe/matter/channel/create?site=' + $scope.site.id, function(rsp) {
                var channel = rsp.data;
                http2.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + $scope.site.id, channel, function(rsp) {
                    $scope.channels.push(rsp.data);
                    location.href = '/rest/pl/fe/matter/channel?site=' + $scope.site.id + '&id=' + channel.id;
                });
            });
        };
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
        $scope.fresh = function(homeChannel) {
            http2.get('/rest/pl/fe/site/setting/page/refreshHomeChannel?site=' + $scope.site.id + '&id=' + homeChannel.id, function(rsp) {
                angular.extend(homeChannel, rsp.data);
            });
        };
        $scope.up = function(homeChannel, index) {
            if (index === 0) return;
            $scope.channels.splice(index, 1);
            $scope.channels.splice(--index, 0, homeChannel);
            updateSeq();
        };
        $scope.down = function(homeChannel, index) {
            if (index === $scope.channels.length - 1) return;
            $scope.channels.splice(index, 1);
            $scope.channels.splice(++index, 0, homeChannel);
            updateSeq();
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            http2.get('/rest/pl/fe/site/setting/page/listHomeChannel?site=' + site.id, function(rsp) {
                $scope.channels = rsp.data;
            });
        });
    }]);
});
