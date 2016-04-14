define(["require", "angular"], function(require, angular) {
    'use strict';
    var loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=4';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var openPlugin = function(content, cb) {
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
            frm.setAttribute('src', content);
        } else {
            if (frm.contentDocument && frm.contentDocument.body) {
                frm.contentDocument.body.innerHTML = content;
            }
        }
    };
    var cookieLogin = function(siteId) {
        var ck, cn, cs, ce, login;
        ck = document.cookie;
        cn = '_site_' + siteId + '_fe_login';
        if (ck.length > 0) {
            cs = ck.indexOf(cn + "=");
            if (cs !== -1) {
                cs = cs + cn.length + 1;
                ce = ck.indexOf(";", cs);
                if (ce === -1) ce = ck.length;
                login = ck.substring(cs, ce);
                return JSON.parse(decodeURIComponent(login));
            }
        }
        return false;
    };
    var app = angular.module('app', []);
    app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
        var ls, siteId, id, shareby;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        id = ls.match(/(\?|&)id=([^&]*)/)[2];
        //shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
        $scope.siteId = siteId;
        $scope.articleId = id;
        $scope.mode = ls.match(/mode=([^&]*)/) ? ls.match(/mode=([^&]*)/)[1] : '';
        var setMpShare = function(xxtShare) {
            var shareid, sharelink;
            shareid = $scope.user.vid + (new Date()).getTime();
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
            //sharelink += '&tpl=std';
            //sharelink += "&shareby=" + shareid;
            xxtShare.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
        };
        var articleLoaded = function() {
            loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
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
                var site = rsp.data.site;
                $scope.article = rsp.data.article;
                $scope.user = rsp.data.user;
                if (site.header_page) {
                    (function() {
                        eval(site.header_page.js);
                    })();
                }
                if (site.footer_page) {
                    (function() {
                        eval(site.footer_page.js);
                    })();
                }
                $scope.site = site;
                window.wx || /Yixin/i.test(navigator.userAgent) && require(['xxt-share'], setMpShare);
                $scope.article.can_picviewer === 'Y' && require(['picviewer']);
                loadCss('/views/default/site/fe/matter/article/main.css');
                deferred.resolve();
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby, {
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
    app.controller('ctrlRemark', ['$scope', '$http', function($scope, $http) {
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
    app.controller('ctrlAlert', ['$scope', function($scope) {
        $scope.close = function() {
            document.querySelector('.weui_dialog_alert').style.display = 'none';
        };
    }]);
    app.controller('ctrlPay', ['$scope', function($scope) {
        $scope.open = function() {
            var url = 'http://' + location.host;
            url += '/rest/coin/pay';
            url += "?mpid=" + $scope.siteId;
            url += "&matter=article," + $scope.articleId;
            openPlugin(url);
        };
    }]);
    app.controller('ctrlFavor', ['$scope', '$http', function($scope, $http) {
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
            if (!cookieLogin($scope.siteId)) {
                var url = 'http://' + location.host;
                url += '/rest/site/fe/user/login';
                url += "?site=" + $scope.siteId;
                openPlugin(url, doFavor);
                return;
            }
            doFavor();
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