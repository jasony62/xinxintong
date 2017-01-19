define(["angular", "xxt-page", "tms-discuss", "tms-coinpay", "tms-favor", "tms-siteuser"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('article', ['discuss.ui.xxt', 'coinpay.ui.xxt', 'favor.ui.xxt', 'siteuser.ui.xxt']);
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
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$q', 'tmsDiscuss', 'tmsCoinPay', 'tmsFavor', 'tmsSiteUser', function($scope, $http, $timeout, $q, tmsDiscuss, tmsCoinPay, tmsFavor, tmsSiteUser) {
        function setMpShare(xxtShare) {
            var shareid, sharelink;
            shareid = $scope.user.uid + (new Date() * 1);
            xxtShare.options.logger = function(shareto) {
                var url = "/rest/site/fe/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&site=" + siteId;
                url += "&id=" + id;
                url += "&type=article";
                url += "&title=" + $scope.article.title;
                url += "&shareto=" + shareto;
                url += "&shareby=" + shareby;
                $http.get(url);
            };
            sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
            sharelink += '?site=' + siteId;
            sharelink += '&type=article';
            sharelink += '&id=' + id;
            sharelink += "&shareby=" + shareid;
            xxtShare.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
        }

        function articleLoaded() {
            window.loading.finish();
            $timeout(function() {
                var audios;
                audios = document.querySelectorAll('audio');
                audios.length > 0 && audios[0].play();
            });
        }

        function loadArticle() {
            var deferred = $q.defer();
            $http.get('/rest/site/fe/matter/article/get?site=' + siteId + '&id=' + id).success(function(rsp) {
                var site = rsp.data.site,
                    mission = rsp.data.mission,
                    article = rsp.data.article,
                    channels = article.channels;

                if (article.use_site_header === 'Y' && site && site.header_page) {
                    codeAssembler.loadCode(ngApp, site.header_page);
                }
                if (article.use_mission_header === 'Y' && mission && mission.header_page) {
                    codeAssembler.loadCode(ngApp, mission.header_page);
                }
                if (article.use_mission_footer === 'Y' && mission && mission.footer_page) {
                    codeAssembler.loadCode(ngApp, mission.footer_page);
                }
                if (article.use_site_footer === 'Y' && site && site.footer_page) {
                    codeAssembler.loadCode(ngApp, site.footer_page);
                }
                if (channels && channels.length) {
                    for (var i = 0, l = channels.length, channel; i < l; i++) {
                        channel = channels[i];
                        if (channel.style_page) {
                            codeAssembler.loadCode(ngApp, channel.style_page);
                        }
                    }
                }
                $scope.site = site;
                $scope.mission = mission;
                $scope.article = article;
                $scope.user = rsp.data.user;
                if (window.wx || /Yixin/i.test(navigator.userAgent)) {
                    require(['xxt-share'], setMpShare);
                }
                article.can_picviewer === 'Y' && require(['picviewer']);
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + id + '&type=article&title=' + article.title + '&shareby=' + shareby, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
                $scope.dataReady = 'Y';
                deferred.resolve();
            }).error(function(content, httpCode) {
                if (httpCode === 401) {
                    codeAssembler.openPlugin(content).then(function() {
                        loadArticle().then(articleLoaded);
                    });
                } else {
                    alert(content);
                }
            });
            return deferred.promise;
        };

        var ls, siteId, id, shareby;

        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        id = ls.match(/(\?|&)id=([^&]*)/)[2];
        shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
        $scope.siteId = siteId;
        $scope.articleId = id;
        $scope.mode = ls.match(/mode=([^&]*)/) ? ls.match(/mode=([^&]*)/)[1] : '';
        $scope.like = function() {
            if ($scope.mode === 'preview') return;
            // var url = "/rest/site/fe/matter/article/score?site=" + $scope.siteId + "&id=" + $scope.articleId;
            // $http.get(url).success(function(rsp) {
            //     $scope.article.score = rsp.data[0];
            //     $scope.article.praised = rsp.data[1];
            // });
        };
        $scope.followYixinMp = function() {
            //location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
        };
        $scope.openChannel = function(ch) {
            location.href = '/rest/site/fe/matter?site=' + $scope.siteId + '&type=channel&id=' + ch.id;
        };
        $scope.searchByTag = function(tag) {
            location.href = '/rest/site/fe/matter/article?site=' + $scope.siteId + '&tagid=' + tag.id;
        };
        $scope.openMatter = function(evt, id, type) {
            evt.preventDefault();
            evt.stopPropagation();
            if (/article|custom|news|channel|link/.test(type)) {
                location.href = '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + id + '&type=' + type;
            } else {
                location.href = '/rest/site/fe/matter/' + type + '?site=' + $scope.siteId + '&app=' + id;
            }
        };
        $scope.downmost = function() {
            var article = $scope.article;
            if (article.can_discuss === 'Y') {
                if (!document.querySelector('.tms-switch-discuss')) {
                    tmsDiscuss.showSwitch(article.siteid, 'article,' + article.id, article.title);
                }
            }
            if (article.can_coinpay === 'Y') {
                if (!document.querySelector('.tms-switch-coinpay')) {
                    tmsCoinPay.showSwitch(article.siteid, 'article,' + article.id);
                }
            }
            if (article.can_siteuser === 'Y') {
                if (!document.querySelector('.tms-switch-siteuser')) {
                    tmsSiteUser.showSwitch(article.siteid, true);
                }
            }
            if (!document.querySelector('.tms-switch-favor')) {
                tmsFavor.showSwitch(article.siteid, article);
            }
            document.querySelector('#gototop').style.display = 'block';
        };
        document.querySelector('#gototop').addEventListener('click', function() {
            document.querySelector('.article').scrollTop = 0;
            this.style.display = 'none';
        });
        loadArticle().then(articleLoaded);
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
    angular._lazyLoadModule('article');
    return {};
});
