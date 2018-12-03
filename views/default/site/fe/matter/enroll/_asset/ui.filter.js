'use strict';

var ngMod = angular.module('filter.ui', ['ui.bootstrap']);
ngMod.directive('tmsFilter', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./tms-filter.html'),
        scope: {
            source: '=',
            confirm: '&'
        },
        link: function(scope, elems, attrs) {
            var _oFiltered, _oCriteriad;
            scope.status = { isopen: false };
            scope.appendToEle = scope.$parent.appendToEle;
            scope.toggled = function(open) {
                if(open) {
                    _oFiltered = angular.extend(_oFiltered, scope.$parent.filter);
                    _oCriteriad = angular.extend(_oCriteriad, scope.$parent.criteria);
                }                
            }
            scope.selected = function(data, menu) {
                _oFiltered[data.type] = menu.value == null ? null : menu;
                _oCriteriad[data.type] = menu.value;
            }
            scope.ok = function(filterOpt) {
                scope.status.isopen = !scope.status.isopen;
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
                scope.filtered = _oFiltered = angular.copy(scope.$parent.filter);
                scope.criteriad = _oCriteriad = angular.copy(scope.$parent.criteria);
            });
        }
    };
}]);