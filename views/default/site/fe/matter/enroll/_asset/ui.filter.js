'use strict';

var ngMod = angular.module('filter.ui', ['ui.bootstrap']);
ngMod.directive('tmsFilter', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-filter.html'),
        scope: {
            source: '=',
            filter: '@',
            criteria: '@',
            confirm: '&'
        },
        link: function(scope, elems, attrs) {
            var _oFiltered, _oCriteriad;
            scope.status = { isopen: false };
            scope.appendToEle = scope.$parent.appendToEle;
            scope.selected = function(data, menu) {
                _oFiltered[data.type] = menu.value == null ? null : menu;
                _oCriteriad[data.type] = menu.value;
            }
            scope.ok = function(filterOpt) {
                scope.status.isopen = !scope.status.isopen;
                function objectKeyIsNull(obj) {
                    var empty = null;
                    for (var i in obj) {
                        if (i!=='isFilter' && i!=='tags') {
                            if(obj[i] !== null) {
                                empty = true;
                                break;
                            }else {
                                empty = false;
                            }
                        }
                    }
                    return empty;
                }
                _oFiltered.isFilter = objectKeyIsNull(_oFiltered) ? true : false;
                scope.confirm({"filterOpt": {"criteria": _oCriteriad, "filter": _oFiltered}});
            };
            scope.clear = function() {
                angular.forEach(scope.datas, function(data) {
                    _oFiltered[data.type] = data.default.value;
                    _oCriteriad[data.type] = data.default.value;
                });
            };
            scope.$watch('source', function(source) {
                if(!source) { return false; }
                scope.datas = source;
            });
            scope.$watch('filter', function(filter) {
                if(!filter) { return false; }
                scope.filtered = _oFiltered = angular.copy(angular.fromJson(scope.filter));
                scope.criteriad = _oCriteriad = angular.copy(angular.fromJson(scope.criteria));
            });
        }
    };
}]);