'use strict';

var ngMod = angular.module('nav.bottom.ui', []);
ngMod.directive('tmsBottomNav', ['$templateCache', function($templateCache) {
    return {
        restrict: 'E',
        replace: true,
        template: require('./tms-bottom-nav.html'),
        scope: {
            navs: '=',
            activeNav: '=',
            type: '@'
        },
        link: function(scope, elems, attrs) {
            scope.switchNav = function($event, nav) {
                location.href = nav.url;
            };
            scope.$watch('navs', function(navs) {
                if (!navs) { return false; }
                navs.forEach(function(nav) {
                    if (nav.type === scope.type) {
                        scope.activeNav = nav;
                    }
                });
            });
        }
    };
}]);