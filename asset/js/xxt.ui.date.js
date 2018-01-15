'use strict';
var ngMod = angular.module('date.ui.xxt', []);
ngMod.filter('tmsDate', ['$filter', function($filter) {
    var i18n = {
        weekday: {
            'Mon': '星期一',
            'Tue': '星期二',
            'Wed': '星期三',
            'Thu': '星期四',
            'Fri': '星期五',
            'Sat': '星期六',
            'Sun': '星期日',
        }
    };

    return function(timestamp, format) {
        var str, weekday;

        if (!format) return timestamp;

        str = $filter('date')(timestamp, format);
        if (format.indexOf('EEE') !== -1) {
            weekday = $filter('date')(timestamp, 'EEE');
            str = str.replace(weekday, i18n.weekday[weekday]);
        }

        return str;
    }
}]);
ngMod.directive('tmsDatepicker', function() {
    var _version = 7;
    return {
        restrict: 'EA',
        scope: {
            date: '=tmsData',
            defaultDate: '@tmsDefaultData',
            mask: '@tmsMask', //y,m,d,h,i
            title: '@tmsTitle',
            state: '@tmsState',
            obj: '=tmsObj'
        },
        templateUrl: '/static/template/datepicker.html?_=' + _version,
        controller: ['$scope', '$uibModal', function($scope, $uibModal) {
            var mask, format = [];
            if ($scope.mask === undefined) {
                mask = {
                    y: true,
                    m: true,
                    d: true,
                    h: true,
                    i: true
                };
                $scope.format = 'yy-MM-dd HH:mm';
            } else {
                mask = (function(mask1) {
                    var mask2, mask1 = mask1.split(',');
                    /*date*/
                    mask2 = {
                        y: mask1[0] === 'y' ? true : mask1[0],
                        m: mask1[1] === 'm' ? true : mask1[1],
                        d: mask1[2] === 'd' ? true : mask1[2],
                    };
                    $scope.format = 'yy-MM-dd';
                    /*time*/
                    if (mask1.length === 5) {
                        if (mask1[3] === 'h') {
                            mask2.h = true;
                            $scope.format += ' HH';
                            if (mask1[4] === 'i') {
                                mask2.i = true;
                                $scope.format += ':mm';
                            } else {
                                mask2.i = mask1[4];
                            }
                        } else {
                            mask2.h = mask1[3];
                            mask2.i = mask1[4] === 'i' ? true : mask1[4];
                        }
                    } else {
                        mask2.h = 0;
                        mask2.i = 0;
                    }
                    return mask2;
                })($scope.mask);
            }
            $scope.open = function() {
                $uibModal.open({
                    templateUrl: 'tmsModalDatepicker.html',
                    resolve: {
                        date: function() {
                            return $scope.date;
                        },
                        defaultDate: function() {
                            return $scope.defaultDate;
                        },
                        mask: function() {
                            return mask;
                        }
                    },
                    controller: ['$scope', '$filter', '$uibModalInstance', 'date', 'defaultDate', 'mask', function($scope, $filter, $mi, date, defaultDate, mask) {
                        date = (function() {
                            var d = new Date();
                            if (defaultDate) {
                                d.setTime(defaultDate ? defaultDate * 1000 : d.getTime());
                            } else {
                                d.setTime(date ? date * 1000 : d.getTime());
                            }
                            d.setMilliseconds(0);
                            d.setSeconds(0);
                            if (mask.i !== true) {
                                d.setMinutes(mask.i);
                            }
                            if (mask.h !== true) {
                                d.setHours(mask.h);
                            }
                            return d;
                        })();
                        $scope.mask = mask;
                        $scope.years = [2015, 2016, 2017, 2018, 2019, 2020];
                        $scope.months = [];
                        $scope.days = [];
                        $scope.hours = [];
                        $scope.minutes = [];
                        $scope.date = {
                            year: date.getFullYear(),
                            month: date.getMonth() + 1,
                            mday: date.getDate(),
                            hour: date.getHours(),
                            minute: date.getMinutes()
                        };
                        for (var i = 1; i <= 12; i++)
                            $scope.months.push(i);
                        for (var i = 1; i <= 31; i++)
                            $scope.days.push(i);
                        for (var i = 0; i <= 23; i++)
                            $scope.hours.push(i);
                        for (var i = 0; i <= 59; i++)
                            $scope.minutes.push(i);
                        $scope.today = function() {
                            var d = new Date();
                            $scope.date = {
                                year: d.getFullYear(),
                                month: d.getMonth() + 1,
                                mday: d.getDate(),
                                hour: d.getHours(),
                                minute: d.getMinutes()
                            };
                        };
                        $scope.reset = function(field) {
                            $scope.date[field] = 0;
                        };
                        $scope.next = function(field, options) {
                            var max = options[options.length - 1];

                            if ($scope.date[field] < max) {
                                $scope.date[field]++;
                            }
                        };
                        $scope.prev = function(field, options) {
                            var min = options[0];

                            if ($scope.date[field] > min) {
                                $scope.date[field]--;
                            }
                        };
                        $scope.ok = function() {
                            $mi.close($scope.date);
                        };
                        $scope.empty = function() {
                            $mi.close(null);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                    }],
                    backdrop: 'static',
                    size: 'sm'
                }).result.then(function(result) {
                    var d;
                    d = result === null ? 0 : Date.parse(result.year + '/' + result.month + '/' + result.mday + ' ' + result.hour + ':' + result.minute) / 1000;
                    $scope.date = d;
                    $scope.$emit('xxt.tms-datepicker.change', {
                        obj: $scope.obj,
                        state: $scope.state,
                        value: d
                    });
                });
            };
        }],
        replace: true
    };
});