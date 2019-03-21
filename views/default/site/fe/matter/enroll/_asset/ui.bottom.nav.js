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
            activeView: '=',
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
                    if (nav.type === 'repos') {
                        nav.views.forEach(function(view) {
                            view.url = '/views/default/site/fe/matter/enroll/template/repos-' + view.type + '.html';
                            if (nav.defaultView.type === view.type) {
                                scope.activeView = view;
                            }
                        });
                    }
                });
            });
        }
    };
}]);