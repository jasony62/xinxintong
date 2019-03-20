'use strict';

var ngMod = angular.module('nav.bottom.ui', []);
ngMod.directive('tmsBottomNav', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-bottom-nav.html'),
        scope: {
            navs: '=',
            switchNav: '&',
            type: '@'
        },
        link: function(scope, elems, attrs) {
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