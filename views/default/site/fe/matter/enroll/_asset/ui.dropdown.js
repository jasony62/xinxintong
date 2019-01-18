'use strict';

var ngMod = angular.module('dropdown.ui', []);
ngMod.directive('tmsDropdown', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-dropdown.html'),
        scope: {
            data: '=basicData',
            criteria: '=',
            shiftMenu: '&'
        },
        link: function(scope, elems, attrs) {
            scope.select = function(id) {
                scope.checked.id = id;
                angular.forEach(scope.data.menus, function(menu) {
                    if (menu.id == id) {
                        scope.checked.title = menu.title;
                    }
                });
                scope.shiftMenu({ "criteria": { "id": id, "type": scope.data.type } });
            };
            scope.$watch('data', function(data) {
                if (!data) { return false; }
                scope.checked = {
                    id: scope.criteria[data.type]
                }
                data.menus.forEach(function(menu) {
                    if (menu.id == scope.checked.id) {
                        scope.checked.title = menu.title;
                        return false;
                    }
                });
            });
        }
    };
}]);