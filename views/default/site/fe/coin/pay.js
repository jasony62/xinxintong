define(["require", "angular"], function(require, angular) {
    'use strict';
    var ngApp = angular.module('app', []),
        ls = location.search,
        siteId = ls.match(/site/) ? ls.match(/(\?|&)site=([^&]*)/)[2] : '',
        matter = ls.match(/matter/) ? ls.match(/(\?|&)matter=([^&]*)/)[2] : '';

    ngApp.controller('ctrlPay', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
        $scope.transfer = function() {
            var url = '/rest/site/fe/coin/pay/payByMatter';
            url += '?site=' + siteId;
            url += '&matter=' + matter;
            $http.post(url, {
                coins: 1
            }).success(function(rsp) {});
        };
        $http.get('/rest/site/fe/coin/pay/pageGet?site=' + siteId + '&matter=' + matter).success(function(rsp) {
            var page = rsp.data.page;
            var pageLoaded = function() {
                $scope.page = page;
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
    ngApp.directive('dynamicHtml', function($compile) {
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