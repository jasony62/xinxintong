define(["require", "angular"], function(require, angular) {
    'use strict';
    var app = angular.module('app', []),
        ls = location.search,
        mpid = ls.match(/mpid/) ? ls.match(/(\?|&)mpid=([^&]*)/)[2] : '',
        matter = ls.match(/matter/) ? ls.match(/(\?|&)matter=([^&]*)/)[2] : '';
    app.controller('ctrlPay', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
        $scope.transfer = function() {
            var url = '/rest/coin/pay/transfer';
            url += '?mpid=' + mpid;
            url += '&matter=' + matter;
            $http.post(url, {
                coins: 1
            }).success(function(rsp) {});
        };
        $http.get('/rest/coin/pay/pageGet?mpid=' + mpid + '&matter=' + matter).success(function(rsp) {
            var page = rsp.data.page;
            var pageLoaded = function() {
                $scope.Page = page;
                window.loading.finish();
            };
            var loadDynaCss = function(css) {
                var style = document.createElement('style');
                style.innerText = css;
                document.querySelector('head').appendChild(style);
            };
            var loadDynaJs = function(page, cb) {
                $timeout(function dynamicjs() {
                    eval(page.js);
                    cb();
                });
            };
            if (page.css && page.css.length) {
                loadDynaCss(page.css);
            }
            if (page.js && page.js.length) {
                loadDynaJs(page, pageLoaded);
            } else {
                pageLoaded();
            }
        });
    }]);
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
});