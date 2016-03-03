define(["require", "angular"], function(require, angular) {
    'use strict';
    var loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=3';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var openPlugin = function(content, cb) {
        window.loading.finish();
        var frag, wrap, frm;
        frag = document.createDocumentFragment();
        wrap = document.createElement('div');
        wrap.setAttribute('id', 'frmPlugin');
        frm = document.createElement('iframe');
        wrap.appendChild(frm);
        wrap.onclick = function() {
            wrap.parentNode.removeChild(wrap);
        };
        frag.appendChild(wrap);
        document.body.appendChild(frag);
        if (content.indexOf('http') === 0) {
            window.onClosePlugin = function() {
                wrap.parentNode.removeChild(wrap);
                cb && cb();
            };
            window.onAuthSuccess = function() {
                wrap.parentNode.removeChild(wrap);
                cb && cb();
            };
            frm.setAttribute('src', content);
        } else {
            if (frm.contentDocument && frm.contentDocument.body) {
                frm.contentDocument.body.innerHTML = content;
            }
        }
    };
    var app = angular.module('app', []);
    app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls, mpid, id, shareby;
        ls = location.search;
        mpid = ls.match(/mpid=([^&]*)/)[1];
        id = ls.match(/(\?|&)id=([^&]*)/)[2];
        shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
        $scope.mpid = mpid;
        $scope.articleId = id;
        $scope.mode = ls.match(/mode=([^&]*)/) ? ls.match(/mode=([^&]*)/)[1] : '';
        var setMpShare = function(xxtShare) {
            var shareid, sharelink;
            shareid = $scope.user.vid + (new Date()).getTime();
            xxtShare.options.logger = function(shareto) {
                var url = "/rest/mi/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&mpid=" + mpid;
                url += "&id=" + id;
                url += "&type=article";
                url += "&title=" + $scope.article.title;
                url += "&shareto=" + shareto;
                url += "&shareby=" + shareby;
                $http.get(url);
            };
            sharelink = 'http://' + location.hostname + '/rest/mi/matter';
            sharelink += '?mpid=' + mpid;
            sharelink += '&type=article';
            sharelink += '&id=' + id;
            sharelink += '&tpl=std';
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
            $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function(rsp) {
                var mpa = rsp.data.mpaccount;
                $scope.article = rsp.data.article;
                $scope.user = rsp.data.user;
                if (mpa.header_page) {
                    (function() {
                        eval(mpa.header_page.js);
                    })();
                }
                if (mpa.footer_page) {
                    (function() {
                        eval(mpa.footer_page.js);
                    })();
                }
                $scope.mpa = mpa;
                /MicroMessenge|Yixin/i.test(navigator.userAgent) && require(['xxt-share'], setMpShare);
                $scope.article.can_picviewer === 'Y' && require(['picviewer']);
                deferred.resolve();
                $http.post('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
            }).error(function(content, httpCode) {
                if (httpCode === 401) {
                    openPlugin(content, function() {
                        loadArticle().then(articleLoaded);
                    });
                } else {
                    alert(content);
                }
            });
            return deferred.promise;
        };
        $scope.like = function() {
            if ($scope.mode === 'preview') return;
            var url = "/rest/mi/article/score?mpid=" + mpid + "&id=" + $scope.articleId;
            $http.get(url).success(function(rsp) {
                $scope.article.score = rsp.data[0];
                $scope.article.praised = rsp.data[1];
            });
        };
        $scope.followYixinMp = function() {
            location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
        };
        $scope.openChannel = function(ch) {
            location.href = '/rest/mi/matter?mpid=' + mpid + '&type=channel&id=' + ch.id;
        };
        $scope.searchByTag = function(tag) {
            location.href = '/rest/mi/article?mpid=' + mpid + '&tagid=' + tag.id;
        };
        $scope.openMatter = function(evt, id, type) {
            evt.preventDefault();
            evt.stopPropagation();
            location.href = '/rest/mi/matter?mpid=' + mpid + '&id=' + id + '&type=' + type + '&tpl=std';
        };
        loadCss('/views/default/article.css');
        loadArticle().then(articleLoaded);
    }]);
    app.controller('ctrlRemark', ['$scope', '$http', function($scope, $http) {
        $scope.newRemark = '';
        $scope.remark = function() {
            var url, param;
            url = "/rest/mi/article/remark?mpid=" + $scope.mpid + "&id=" + $scope.articleId;
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
    app.controller('ctrlPay', ['$scope', function($scope) {
        $scope.open = function() {
            var url = 'http://' + location.host;
            url += '/rest/coin/pay';
            url += "?mpid=" + $scope.mpid;
            url += "&matter=article," + $scope.articleId;
            openPlugin(url);
        };
    }]);
    app.directive('dynamicHtml', function($compile) {
        return {
            restrict: 'EA',
            replace: true,
            link: function(scope, ele, attrs) {
                scope.$watch(attrs.dynamicHtml, function(html) {
                    if (html && html.length) {
                        ele.html(html);
                        $compile(ele.contents())(scope);
                    }
                });
            }
        };
    });
    app.filter('filesize', function() {
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
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});