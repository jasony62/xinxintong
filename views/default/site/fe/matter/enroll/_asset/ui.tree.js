'use strict';

var ngMod = angular.module('tree.ui', []);
ngMod.directive('tmsTree', ['$templateCache', function($templateCache) {
    return {
        restrict: 'E',
        template: require('./tms-tree.html'),
        scope: {
            treeData: '=',
            checkedData: '@',
            dirClicked: '&',
            dirIcon: '='
        },
        controller: ['$scope', '$q', function($scope, $q) {
            function _getMaxFloor(treeData) {
                var floor = 0,
                    max = 0,
                    defer = $q.defer();
                (function each(datas, floor) {
                    datas.forEach(function(data) {
                        if (Object.keys(data).indexOf('floor') !== -1) {
                            return false;
                        } else {
                            data.floor = floor;
                            if (floor > max) {
                                max = floor;
                            }
                            if (data.op.childrenDir && data.op.childrenDir.length > 0) {
                                each(data.op.childrenDir, floor + 1);
                            }
                        }
                    });
                })(treeData, 1);
                defer.resolve(max);
                return defer.promise;
            }

            $scope.hasChild = function(item) {
                return !item.op.childrenDir || !item.op.childrenDir.length;
            };

            $scope.itemExpended = function(item, $event) {
                $scope.treeData.forEach(function(data) {
                    if(data.schema_id==item.schema_id&&data.op.v==item.op.v) {
                        data.opened = !data.opened;
                        $scope.active[1] = data;
                    }
                });
                $event.stopPropagation();
            };

            $scope.wrapCallback = function(callback, item, actived) {
                if (item) {
                    if (item.floor == 1) {
                        item.opened = item.op.childrenDir && item.op.childrenDir.length ? true : false;
                    }
                    $scope.active[item.floor] = item;
                    for (var i in actived) {
                        if (i > item.floor) {
                            $scope.active[i] = "";
                        }
                    }
                    ($scope[callback] || angular.noop)({
                        $item: item,
                        $active: actived
                    });
                } else {
                    $scope.active = {};
                    ($scope[callback] || angular.noop)({
                        $item: null,
                        $active: $scope.active
                    });
                }
            };

            $scope.$watch('treeData', function(datas) {
                if (!datas) { return false; }
                $scope.active = angular.copy(angular.fromJson($scope.checkedData));
                if (datas && datas.length) {
                    _getMaxFloor(datas).then(function(num) {
                        if (num) {
                            for (var i = 1; i <= num; i++) {
                                $scope.active[i] = '';
                            }
                        }                   
                    });
                }
            });
        }]
    };
}]);