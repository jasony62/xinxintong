'use strict';

var ngMod = angular.module('filter.ui', ['ui.bootstrap']);
ngMod.directive('tmsFilter', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-filter.html'),
        scope: {
            datas: '=basicData',
            filter: '=',
            criteria: '=',
            confirm: '&'
        },
        link: function(scope, elems, attrs) {
            scope.status = { isopen: false };
            scope.appendToEle = scope.$parent.appendToEle;
            scope.selected = function(data, menu) {
                scope.filter[data.type] = menu.id == null ? null : menu;
                scope.criteria[data.type] = menu.id;
            }
            scope.ok = function(filterOpt) {
                scope.status.isopen = !scope.status.isopen;
                if (scope.criteria.keyword) {
                    scope.filter.keyword = { 'title': scope.criteria.keyword, 'id': scope.criteria.keyword };
                } else {
                    scope.criteria.keyword = scope.filter.keyword = null;
                }

                function objectKeyIsNull(obj) {
                    var empty = null;
                    for (var i in obj) {
                        if (i !== 'isFilter' && i !== 'tags') {
                            if (obj[i] !== null) {
                                empty = true;
                                break;
                            } else {
                                empty = false;
                            }
                        }
                    }
                    return empty;
                }
                scope.filter.isFilter = objectKeyIsNull(scope.filter) ? true : false;
                scope.confirm({ "filterOpt": { "criteria": scope.criteria, "filter": scope.filter } });
            };
            scope.clear = function() {
                angular.forEach(scope.datas, function(data) {
                    scope.filter[data.type] = data.default.id;
                    scope.criteria[data.type] = data.default.id;
                });
            };
            scope.$watch('datas', function(datas) {
                if (!datas) { return false; }
                scope.datas = angular.fromJson(datas);
            });

        }
    };
}]);