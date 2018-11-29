'use strict';

var ngMod = angular.module('dropdown.ui', []);
ngMod.directive('tmsDropdown', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-dropdown.html'),
        scope: {
            data: '=',
            changeMenu: '&'
        },
        link: function(scope, elems, attrs) {
            scope.select = function(value) {
                scope.criteria.value = value;
                angular.forEach(scope.data.menus, function(menu) {
                    if (menu.value == value) {
                        scope.criteria.title = menu.title;
                    }
                });
                scope.changeMenu({"criteria": {"menu": value, "type": scope.data.type}});
            };
            scope.$watch('data', function(data) {
                if( !data ) { return; }
                scope.criteria = {
                    value: scope.data.default.value,
                    title: scope.data.default.title
                };
            });
        }
    };
}]);