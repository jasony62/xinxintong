define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('article', []);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls, siteId, id, shareby;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        id = ls.match(/(\?|&)id=([^&]*)/)[2];
        shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
        $scope.siteId = siteId;
        $scope.articleId = id;
        $scope.mode = ls.match(/mode=([^&]*)/) ? ls.match(/mode=([^&]*)/)[1] : '';
        var setMpShare = function(xxtShare) {
            var shareid, sharelink;
            shareid = $scope.user.uid + (new Date()).getTime();
            xxtShare.options.logger = function(shareto) {
                /*var url = "/rest/mi/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&site=" + siteId;
                url += "&id=" + id;
                url += "&type=article";
                url += "&title=" + $scope.article.title;
                url += "&shareto=" + shareto;
                //url += "&shareby=" + shareby;
                $http.get(url);*/
            };
            sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
            sharelink += '?site=' + siteId;
            sharelink += '&type=article';
            sharelink += '&id=' + id;
            sharelink += "&shareby=" + shareid;
            xxtShare.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
        };
        var articleLoaded = function() {
            window.loading.finish();
            $timeout(function() {
                var audios;
                audios = document.querySelectorAll('audio');
                audios.length > 0 && audios[0].play();
            });
        };
        var loadArticle = function() {
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
        $scope.AlterMsg = {
            title: '',
            msg: ''
        };
        $scope.like = function() {
            if ($scope.mode === 'preview') return;
            var url = "/rest/site/fe/matter/article/score?site=" + $scope.siteId + "&id=" + $scope.articleId;
            $http.get(url).success(function(rsp) {
                $scope.article.score = rsp.data[0];
                $scope.article.praised = rsp.data[1];
            });
        };
        $scope.followYixinMp = function() {
            location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
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
            location.href = '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + id + '&type=' + type + '&tpl=std';
        };
        loadArticle().then(articleLoaded);
    }]);
    ngApp.controller('ctrlRemark', ['$scope', '$http', function($scope, $http) {
        $scope.newRemark = '';
        $scope.remark = function() {
            var url, param;
            url = "/rest/site/fe/matter/article/remark?site=" + $scope.siteId + "&id=" + $scope.articleId;
            param = {
                remark: $scope.newRemark
            };
            $http.post(url, param).success(function(rsp) {
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                };
                $scope.newRemark = '';
                $scope.article.remarks === false ? $scope.article.remarks = [rsp.data] : $scope.article.remarks.splice(0, 0, rsp.data);
            });
        };
        $scope.reply = function(remark) {
            $scope.newRemark += '@' + remark.nickname;
            $timeout(function() {
                document.querySelector('#gotoNewRemark').click();
            });
        };
    }]);
    ngApp.controller('ctrlAlert', ['$scope', function($scope) {
        $scope.close = function() {
            document.querySelector('.weui_dialog_alert').style.display = 'none';
        };
    }]);
    ngApp.controller('ctrlPay', ['$scope', function($scope) {
        $scope.open = function() {
            var url = 'http://' + location.host;
            url += '/rest/coin/pay';
            url += "?mpid=" + $scope.siteId;
            url += "&matter=article," + $scope.articleId;
            openPlugin(url);
        };
    }]);
    ngApp.controller('ctrlFavor', ['$scope', '$http', function($scope, $http) {
        var doFavor = function() {
            var url = "/rest/site/fe/user/favor/add?site=" + $scope.siteId + "&id=" + $scope.article.id + '&type=article' + '&title=' + $scope.article.title;
            $http.get(url).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.AlterMsg.title = '操作失败';
                    $scope.AlterMsg.msg = rsp.err_msg;
                    document.querySelector('.weui_dialog_alert').style.display = 'block';
                }
            });
        };
        $scope.favor = function() {
            if ($scope.mode === 'preview') return;
            if (!codeAssembler.cookieLogin($scope.siteId)) {
                var url = 'http://' + location.host;
                url += '/rest/site/fe/user/login';
                url += "?site=" + $scope.siteId;
                openPlugin(url, doFavor);
                return;
            }
            doFavor();
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
    angular._lazyLoadModule('article');
    return {};
});