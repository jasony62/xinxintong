'use strict';

var ngMod = angular.module('filter.ui', []);
ngMod.directive('tmsFilter', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-filter.html'),
        scope: {
            datas: '=',
            filter: '=',
            criteria: '=',
            confirm: '&'
        },
        link: function(scope, elems, attrs) {
            scope.status = { isopen: false };
            scope.appendToEle = scope.$parent.appendToEle;

            scope.selected = function(data, menu) {
                scope.filter[data.type] = menu.value == data.default.value ? data.default.value : menu;
                scope.criteria[data.type] = menu.value;
            }
            scope.ok = function(filter) {
                scope.status.isopen = !scope.status.isopen;
                scope.confirm({"filter": {"criteria": scope.criteria, "filter": filter}});
            };
            scope.cancle = function() {
                angular.forEach(scope.datas, function(data) {
                    scope.filter[data.type] = data.default.value;
                    scope.criteria[data.type] = data.default.value;
                });
            };
        }
    };
}]);