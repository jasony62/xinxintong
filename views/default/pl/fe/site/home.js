define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlHome', ['$scope', 'http2', 'noticebox', 'mediagallery', function($scope, http2, noticebox, mediagallery) {
        var recommenSite, navSite;
        $scope.state = 'N';
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
        $scope.setHomeHeadingPic = function() {
            var options = {
                callback: function(url) {
                    $scope.site.home_heading_pic = url + '?_=' + (new Date * 1);
                    $scope.update('home_heading_pic');
                }
            };
            mediagallery.open($scope.site.id, options);
        };
        $scope.removeHomeHeadingPic = function() {
            $scope.site.home_heading_pic = '';
            $scope.update('home_heading_pic');
        };
        $scope.update = function(name) {
            if (name !== 'autoup_homepage' || window.confirm('勾选后，如果团队主页页面有更新将会自动覆盖现有主页页面，确定自动更新？')) {
                var p = {};
                p[name] = $scope.site[name];
                http2.post('/rest/pl/fe/site/update?site=' + $scope.site.id, p, function(rsp) {});
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
            http2.get(url, function(rsp) {
                $scope.state = 'Y';
            });
        };
        $scope.cancleToHome = function() {
            if ((recommenSite && recommenSite.approved == 'Y') || navSite) {
                noticebox.error('团队已推荐到平台主页或发布到平台主导航条，不允许禁止');
            } else {
                $scope.state = 'N';
            }
        }
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
        /*http2.get('/rest/pl/be/platform/get', function(rsp) {
            if (rsp.data.home_nav) {
                $scope.home_nav = rsp.data.home_nav;
                $scope.home_nav.forEach(function(item) {
                    if (item.site.id == $scope.site.id) {
                        $scope.navSite = navSite = item;
                    }
                })
            }
        })
        http2.get('/rest/pl/be/home/recommend/listSite', function(rsp) {
            $scope.sites = rsp.data.sites;
            $scope.sites.forEach(function(item) {
                if (item.siteid == $scope.site.id) {
                    $scope.recommenSite = recommenSite = item;
                    $scope.state = 'Y';
                }
            });
        });*/
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
    ngApp.provider.controller('ctrlHomeQrcode', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        function update(name) {
            var p = {},
                site = $scope.site;
            p[name] = site[name];
            http2.post('/rest/pl/fe/site/update?site=' + site.id, p, function(rsp) {});
        }
        var qrcodes;
        $scope.add = function() {
            var options = {
                callback: function(url) {
                    qrcodes.push({
                        picUrl: url + '?_=' + (new Date() * 1)
                    });
                    update('home_qrcode_group');
                }
            };
            mediagallery.open($scope.site.id, options);
        };
        $scope.doTip = function(tip) {
            update('home_qrcode_group');
        }
        $scope.remove = function(slide, index) {
            qrcodes.splice(index, 1);
            update('home_qrcode_group');
        };
        $scope.up = function(slide, index) {
            if (index === 0) return;
            qrcodes.splice(index, 1);
            qrcodes.splice(--index, 0, slide);
            update('home_qrcode_group');
        };
        $scope.down = function(slide, index) {
            if (index === qrcodes.length - 1) return;
            qrcodes.splice(index, 1);
            qrcodes.splice(++index, 0, slide);
            update('home_qrcode_group');
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            if (!site.home_qrcode_group) site.home_qrcode_group = [];
            qrcodes = site.home_qrcode_group;
            $scope.qrcodes = qrcodes;
        });
    }]);
    ngApp.provider.controller('ctrlHomeChannel', ['$scope', '$uibModal', 'http2', 'srvSite', 'noticebox', function($scope, $uibModal, http2, srvSite, noticebox) {
        $scope.doGroup = function(channel, group) {
            var url = '/rest/pl/fe/site/setting/page/updateHomeChannel';
            url += '?site=' + channel.siteid + '&id=' + channel.id;
            http2.post(url, { homeGroup: group }, function(rsp) {
                noticebox.success('完成分组');
            });
        }
        $scope.edit = function(channel) {
            $uibModal.open({
                templateUrl: 'editChannelTitle.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.channel = angular.copy(channel);
                    $scope2.ok = function() {
                        $mi.close($scope2.channel);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                backdrop: 'static'
            }).result.then(function(newChannel) {
                channel.display_name = newChannel.display_name;
                var url = '/rest/pl/fe/site/setting/page/updateHomeChannel';
                url += '?site=' + channel.siteid + '&id=' + channel.id;
                http2.post(url, { display_name: channel.display_name }, function(rsp) {
                    noticebox.success('完成更新');
                });
            });
        };

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
            srvSite.openGallery(options).then(function(channels) {
                var channel;
                if (channels && channels.matters.length) {
                    channel = channels.matters[0];
                    http2.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + $scope.site.id, channel, function(rsp) {
                        $scope.channels.push(rsp.data);
                    });
                }
            });
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