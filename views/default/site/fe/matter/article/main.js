'use strict';

require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');
require('../../../../../../asset/js/xxt.ui.subscribe.js');
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.forward.js');
require('../../../../../../asset/js/xxt.ui.coinpay.js');
require('../../../../../../asset/js/xxt.ui.share.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'page.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt', 'subscribe.ui.xxt', 'favor.ui.xxt', 'forward.ui.xxt', 'coinpay.ui.xxt']);
ngApp.config(['$controllerProvider', function($cp) {
    ngApp.provider = {
        controller: $cp.register
    };
}]);
ngApp.directive('tmsScroll', [function() {
    function _endScroll(event, $scope) {
        var target = event.target,
            scrollTop = target.scrollTop;

        if (scrollTop === 0) {
            if ($scope.$parent.uppermost) {
                $scope.$parent.uppermost(target);
            }
        } else if (scrollTop === target.scrollHeight - target.clientHeight) {
            if ($scope.$parent.downmost) {
                $scope.$parent.downmost(target);
            }
        } else {
            if (target.__lastScrollTop === undefined || scrollTop > target.__lastScrollTop) {
                if ($scope.$parent.upward) {
                    $scope.$parent.upward(target);
                }
            } else {
                if ($scope.$parent.downward) {
                    $scope.$parent.downward(target);
                }
            }
        }
        target.__lastScrollTop = scrollTop;
    }

    function _domReady($scope, elems) {
        for (var i = elems.length - 1; i >= 0; i--) {
            if (elems[i].scrollHeight === elems[i].clientHeight) {
                if ($scope.downmost && angular.isString($scope.downmost) && $scope.$parent.downmost) {
                    $scope.$parent.downmost(elems[i]);
                }
            }
        }
    }

    return {
        restrict: 'EA',
        scope: {
            upward: '@',
            downward: '@',
            uppermost: '@',
            downmost: '@',
            ready: '=',
        },
        link: function($scope, elems, attrs) {
            if (attrs.ready) {
                $scope.$watch('ready', function(ready) {
                    if (ready === 'Y') {
                        _domReady($scope, elems);
                    }
                });
            } else {
                /* link发生在load之前 */
                window.addEventListener('load', function() {
                    _domReady($scope, elems);
                });
            }
            for (var i = elems.length - 1; i >= 0; i--) {
                elems[i].onscroll = function(event) {
                    var target = event.target;
                    if (target.__timer) {
                        clearTimeout(target.__timer);
                    }
                    target.__timer = setTimeout(function() {
                        _endScroll(event, $scope);
                    }, 35);
                };
            }
        }
    };
}]);
ngApp.filter('filesize', function() {
    return function(length) {
        var unit;
        if (length / 1024 < 1) {
            unit = 'B';
        } else {
            length = length / 1024;
            if (length / 1024 < 1) {
                unit = 'K';
            } else {
                length = length / 1024;
                unit = 'M';
            }
        }
        length = (new Number(length)).toFixed(2);

        return length + unit;
    };
});
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', '$q', 'tmsDynaPage', 'tmsSubscribe', 'tmsSnsShare', 'tmsCoinPay', 'tmsFavor', 'tmsForward', 'tmsSiteUser', function($scope, $http, $timeout, $q, tmsDynaPage, tmsSubscribe, tmsSnsShare, tmsCoinPay, tmsFavor, tmsForward, tmsSiteUser) {
    var width = document.body.clientWidth;
    $scope.width = width;

    function finish() {
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    }

    function articleLoaded() {
        finish();
        $timeout(function() {
            var audios;
            audios = document.querySelectorAll('audio');
            audios.length > 0 && audios[0].play();
        });
        $scope.code = '/rest/site/fe/matter/article/qrcode?site=' + siteId + '&url=' + encodeURIComponent(location.href);
        if (window.sessionStorage) {
            var pendingMethod;
            if (pendingMethod = window.sessionStorage.getItem('xxt.site.fe.matter.article.auth.pending')) {
                window.sessionStorage.removeItem('xxt.site.fe.matter.article.auth.pending');
                if ($scope.user.loginExpire) {
                    pendingMethod = JSON.parse(pendingMethod);
                    $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                }
            }
        }
    }

    function loadArticle() {
        var deferred = $q.defer();
        $http.get('/rest/site/fe/matter/article/get?site=' + siteId + '&id=' + id).success(function(rsp) {
            var site = rsp.data.site,
                mission = rsp.data.mission,
                oArticle = rsp.data.article,
                channels = oArticle.channels;

            if (oArticle.use_site_header === 'Y' && site && site.header_page) {
                tmsDynaPage.loadCode(ngApp, site.header_page);
            }
            if (oArticle.use_mission_header === 'Y' && mission && mission.header_page) {
                tmsDynaPage.loadCode(ngApp, mission.header_page);
            }
            if (oArticle.use_mission_footer === 'Y' && mission && mission.footer_page) {
                tmsDynaPage.loadCode(ngApp, mission.footer_page);
            }
            if (oArticle.use_site_footer === 'Y' && site && site.footer_page) {
                tmsDynaPage.loadCode(ngApp, site.footer_page);
            }
            if (channels && channels.length) {
                for (var i = 0, l = channels.length, channel; i < l; i++) {
                    channel = channels[i];
                    if (channel.style_page) {
                        tmsDynaPage.loadCode(ngApp, channel.style_page);
                    }
                }
            }
            $scope.site = site;
            $scope.mission = mission;
            $scope.article = oArticle;
            $scope.user = rsp.data.user;
            /* 设置分享 */
            if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
                var shareid, sharelink, shareby;
                shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
                shareid = $scope.user.uid + '_' + (new Date() * 1);
                sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
                sharelink += '?site=' + siteId;
                sharelink += '&type=article';
                sharelink += '&id=' + id;
                sharelink += "&shareby=" + shareid;
                tmsSnsShare.config({
                    siteId: siteId,
                    logger: function(shareto) {
                        var url = "/rest/site/fe/matter/logShare";
                        url += "?shareid=" + shareid;
                        url += "&site=" + siteId;
                        url += "&id=" + id;
                        url += "&type=article";
                        url += "&title=" + oArticle.title;
                        url += "&shareto=" + shareto;
                        url += "&shareby=" + shareby;
                        $http.get(url);
                    },
                    jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
                });
                tmsSnsShare.set(oArticle.title, sharelink, oArticle.summary, oArticle.pic);
            }

            if (oArticle.can_picviewer === 'Y') {
                tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
            }
            if (!document.querySelector('.tms-switch-favor')) {
                tmsFavor.showSwitch($scope.user, oArticle);
            } else {
                $scope.favor = function(user, article) {
                    if (!user.loginExpire) {
                        tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + article.siteid).then(function(data) {
                            user.loginExpire = data.loginExpire;
                            tmsFavor.open(article);
                        });
                    } else {
                        tmsFavor.open(article);
                    }
                }
            }
            if (!document.querySelector('.tms-switch-forward')) {
                tmsForward.showSwitch($scope.user, oArticle);
            } else {
                $scope.forward = function(user, article) {
                    if (!user.loginExpire) {
                        tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + article.siteid).then(function(data) {
                            user.loginExpire = data.loginExpire;
                            tmsForward.open(article);
                        });
                    } else {
                        tmsForward.open(article);
                    }
                }
            }
            if (oArticle.can_coinpay === 'Y') {
                if (!document.querySelector('.tms-switch-coinpay')) {
                    tmsCoinPay.showSwitch(oArticle.siteid, 'article,' + oArticle.id);
                }
            }
            if (oArticle.can_siteuser === 'Y') {
                if (!document.querySelector('.tms-switch-siteuser')) {
                    tmsSiteUser.showSwitch(oArticle.siteid, true);
                } else {
                    $scope.siteUser = function(id) {
                        var url = 'http://' + location.host;
                        url += '/rest/site/fe/user';
                        url += "?site=" + siteId;
                        location.href = url;
                    }
                }
            }
            if (!_bPreview) {
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + id + '&type=article&title=' + oArticle.title + '&shareby=' + shareby, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
            }
            $scope.dataReady = 'Y';
            deferred.resolve();
        }).error(function(content, httpCode) {
            finish();
            if (httpCode === 401) {
                tmsDynaPage.openPlugin(content).then(function() {
                    loadArticle().then(articleLoaded);
                });
            } else {
                alert(content);
            }
        });
        return deferred.promise;
    };

    var ls, siteId, id, _bPreview;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    id = ls.match(/[\?|&]id=([^&]*)/)[1];
    _bPreview = ls.match(/[\?|&]preview=Y/);

    $scope.elSiteCard = angular.element(document.querySelector('#site-card'));
    $scope.siteCardToggled = function(open) {
        var elDropdownMenu;
        if (open) {
            if (elDropdownMenu = document.querySelector('#site-card>.dropdown-menu')) {
                elDropdownMenu.style.left = 'auto';
                elDropdownMenu.style.right = 0;
            }
        }
    };
    $scope.openChannel = function(ch) {
        location.href = '/rest/site/fe/matter?site=' + siteId + '&type=channel&id=' + ch.id;
    };
    $scope.searchByTag = function(tag) {
        location.href = '/rest/site/fe/matter/article?site=' + siteId + '&tagid=' + tag.id;
    };
    $scope.openMatter = function(evt, id, type) {
        evt.preventDefault();
        evt.stopPropagation();
        if (/article|custom|news|channel|link/.test(type)) {
            location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + id + '&type=' + type;
        } else {
            location.href = '/rest/site/fe/matter/' + type + '?site=' + siteId + '&app=' + id;
        }
    };
    $scope.subscribeSite = function() {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                });
                window.sessionStorage.setItem('xxt.site.fe.matter.article.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/login?site=' + siteId;
        } else {
            tmsSubscribe.open($scope.user, $scope.site);
        }
    };
    document.querySelector('#gototop').addEventListener('click', function() {
        document.querySelector('.article').scrollTop = 0;
    });
    loadArticle().then(articleLoaded);
}]);