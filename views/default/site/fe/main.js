define(['require', 'angular'], function(require, angular) {
    'use strict';
    var site = location.search.match('[\?&]site=([^&]*)')[1];
    var loadCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=3';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var cookieLogin = function() {
        var ck, cn, cs, ce, login;
        ck = document.cookie;
        cn = '_site_' + site + '_fe_login';
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
    var setPage = function($scope, page) {
        if (page.ext_css && page.ext_css.length) {
            angular.forEach(page.ext_css, function(css) {
                loadCss(css.url);
            });
        }
        if (page.css && page.css.length) {
            var style = document.createElement('style'),
                head = document.querySelector('head');
            style.innerHTML = page.css;
            head.appendChild(style);
        }
        if (page.ext_js && page.ext_js.length) {
            var i, l, loadJs;
            i = 0;
            l = page.ext_js.length;
            loadJs = function() {
                var js;
                js = page.ext_js[i];
                $.getScript(js.url, function() {
                    i++;
                    if (i === l) {
                        if (page.js && page.js.length) {
                            $scope.$apply(
                                function dynamicjs() {
                                    eval(page.js);
                                    $scope.Page = page;
                                }
                            );
                        }
                    } else {
                        loadJs();
                    }
                });
            };
            loadJs();
        } else if (page.js && page.js.length) {
            (function dynamicjs() {
                eval(page.js);
                $scope.Page = page;
            })();
        } else {
            $scope.Page = page;
        }
    };
    var app = angular.module('app', []);
    app.directive('dynamicHtml', function($compile) {
        return {
            restrict: 'A',
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
    app.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
        $scope.gotoRegister = function() {
            location.href = '/rest/site/fe/user/register?site=' + site;
        };
        $scope.gotoLogin = function() {
            location.href = '/rest/site/fe/user/login?site=' + site;
        };
        $scope.gotoSetting = function() {
            location.href = '/rest/site/fe/user/setting?site=' + site;
        };
        $scope.quitLogin = function() {
            $http.get('/rest/site/fe/user/logout/do?site=' + site).success(function(rsp) {
                $scope.login = false;
            });
        };
        $http.get('/rest/site/fe/pageGet?site=' + site).success(function(rsp) {
            setPage($scope, rsp.data.page);
        });
        loadCss("https://res.wx.qq.com/open/libs/weui/0.3.0/weui.min.css");
        $scope.login = cookieLogin();
        window.loading.finish();
    }]);
});