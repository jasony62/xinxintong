define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('custom', []);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$q', function($scope, $http, $q) {
        var ls, siteId, id, shareby;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        id = ls.match(/[\?&]id=([^&]*)/)[1];
        shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
        var setMpShare = function(xxtShare) {
            var shareid, sharelink;
            //shareid = $scope.user.vid + (new Date()).getTime();
            xxtShare.options.logger = function(shareto) {
                /*var url = "/rest/mi/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&site=" + siteId;
                url += "&id=" + id;
                url += "&type=article";
                url += "&title=" + $scope.article.title;
                url += "&shareto=" + shareto;
                url += "&shareby=" + shareby;
                $http.get(url);*/
            };
            sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
            sharelink += '?siteId=' + siteId;
            sharelink += '&type=custom';
            sharelink += '&id=' + id;
            //sharelink += "&shareby=" + shareid;
            xxtShare.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
        };
        var articleLoaded = function() {
            /MicroMessenge|Yixin/i.test(navigator.userAgent) && require(['xxt-share'], function(xxtShare) {
                setMpShare(xxtShare);
            });
            window.loading.finish();
        };
        var loadArticle = function() {
            var deferred = $q.defer();
            $http.get('/rest/site/fe/matter/article/get?site=' + siteId + '&id=' + id).success(function(rsp) {
                var site = rsp.data.site,
                    article = rsp.data.article,
                    channels = article.channels,
                    page = article.page;
                if (article.use_site_header === 'Y' && site && site.header_page) {
                    codeAssembler.loadCode(ngApp, site.header_page);
                }
                if (article.use_site_footer === 'Y' && site && site.footer_page) {
                    codeAssembler.loadCode(ngApp, site.footer_page);
                }
                codeAssembler.loadCode(ngApp, page).then(function() {
                    $scope.article = article;
                });
                if (channels && channels.length) {
                    for (var i = 0, l = channels.length, channel; i < l; i++) {
                        channel = channels[i];
                        if (channel.style_page) {
                            codeAssembler.loadCode(ngApp, channel.style_page);
                        }
                    }
                }
                $scope.user = rsp.data.user;
                $scope.site = site;
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + id + '&type=article&title=' + article.title + '&shareby=' + shareby, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
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
        loadArticle().then(articleLoaded);
    }]);

    angular._lazyLoadModule('custom');

    return {};
});