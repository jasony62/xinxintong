'use strict';

var ngMod = angular.module('dropdown.ui', []);
ngMod.directive('tmsDropdown', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-dropdown.html'),
        scope: {
            data: '=',
            shiftMenu: '&'
        },
        link: function(scope, elems, attrs) {
            scope.select = function(value) {
                scope.checked.value = value;
                angular.forEach(scope.data.menus, function(menu) {
                    if (menu.value == value) {
                        scope.checked.title = menu.title;
                    }
                });
                scope.shiftMenu({"criteria": {"value": value, "type": scope.data.type}});
            };
            scope.$watch('data', function(data) {
                if( !data ) { return; }
                scope.checked = {
                    value: scope.data.default.value,
                    title: scope.data.default.title
                };
            });
        }
    };
}]);