define(["enroll-common"], function(ngApp) {
    'use strict';
    var __util = {};
    __util.makeDialog = function(id, html) {
        var dlg, $dlg;
        dlg = "<div class='dialog mask'><div class='dialog dlg'>";
        html.header && html.header.length && (dlg += "<div class='dlg-header'>" + html.header + "</div>");
        dlg += "<div class='dlg-body'>" + html.body + "</div>";
        html.footer && html.fotter.length && (dlg += "<div class='dlg-footer'>" + html.footer + "</div>");
        dlg += "</div></div>";
        $dlg = $(dlg).attr('id', id);
        $('body').append($dlg);
        return $dlg.contents();
    };
    ngApp.directive('tmsDate', ['$compile', function($compile) {
        return {
            restrict: 'A',
            scope: {
                value: '=tmsDateValue'
            },
            controller: function($scope) {
                $scope.close = function() {
                    $scope.opened = false;
                    $('#' + $scope.dialogID).remove();
                };
                $scope.ok = function() {
                    var dtObject;
                    dtObject = new Date();
                    dtObject.setTime(0);
                    dtObject.setFullYear($scope.data.year);
                    dtObject.setMonth($scope.data.month - 1);
                    dtObject.setDate($scope.data.date);
                    dtObject.setHours($scope.data.hour);
                    dtObject.setMinutes($scope.data.minute);
                    $scope.value = dtObject.getTime();
                    $scope.close();
                };
            },
            link: function(scope, elem, attrs) {
                var fnOpenPicker, dtObject, dtMinute, htmlBody;
                scope.value === undefined && (scope.value = new Date().getTime());
                dtObject = new Date();
                dtObject.setTime(scope.value);
                scope.options = {
                    years: [2014, 2015, 2016, 2017],
                    months: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                    dates: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31],
                    hours: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
                    minutes: [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55],
                };
                dtMinute = Math.round(dtObject.getMinutes() / 5) * 5;
                scope.data = {
                    year: dtObject.getFullYear(),
                    month: dtObject.getMonth() + 1,
                    date: dtObject.getDate(),
                    hour: dtObject.getHours(),
                    minute: dtMinute
                };
                scope.options.minutes.indexOf(dtMinute) === -1 && scope.options.minutes.push(dtMinute);
                htmlBody = '<div class="form-group"><select class="form-control" ng-model="data.year" ng-options="y for y in options.years"></select></div>';
                htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.month" ng-options="m for m in options.months"></select></div>';
                htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.date" ng-options="d for d in options.dates"></select></div>';
                htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.hour" ng-options="h for h in options.hours"></select></div>';
                htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.minute" ng-options="mi for mi in options.minutes"></select></div>';
                fnOpenPicker = function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (scope.opened) return;
                    var html, id;
                    id = '_dlg-' + (new Date()).getTime();
                    html = {
                        header: '',
                        body: htmlBody,
                        footer: '<button class="btn btn-default" ng-click="close()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button>'
                    };
                    html = __util.makeDialog(id, html);
                    scope.opened = true;
                    scope.dialogID = id;
                    $compile(html)(scope);
                };
                $(elem).find('[ng-bind]').click(fnOpenPicker);
            }
        }
    }]);
    ngApp.directive('tmsCheckboxGroup', function() {
        return {
            restrict: 'A',
            link: function(scope, elem, attrs) {
                var groupName, model, options, upper;
                if (attrs.tmsCheckboxGroup && attrs.tmsCheckboxGroup.length) {
                    groupName = attrs.tmsCheckboxGroup;
                    if (attrs.tmsCheckboxGroupModel && attrs.tmsCheckboxGroupModel.length) {
                        model = attrs.tmsCheckboxGroupModel;
                        if (attrs.tmsCheckboxGroupUpper && attrs.tmsCheckboxGroupUpper.length) {
                            upper = attrs.tmsCheckboxGroupUpper;
                            options = document.querySelectorAll('[name=' + groupName + ']');
                            scope.$watch(model + '.' + groupName, function(data) {
                                var cnt;
                                cnt = 0;
                                angular.forEach(data, function(v, p) {
                                    v && cnt++;
                                });
                                if (cnt >= upper) {
                                    [].forEach.call(options, function(el) {
                                        if (el.checked === undefined) {
                                            !el.classList.contains('checked') && el.setAttribute('disabled', true);
                                        } else {
                                            !el.checked && (el.disabled = true);
                                        }
                                    });
                                } else {
                                    [].forEach.call(options, function(el) {
                                        if (el.checked === undefined) {
                                            el.removeAttribute('disabled');
                                        } else {
                                            el.disabled = false;
                                        }
                                    });
                                }
                            }, true);
                        }
                    }
                }
            }
        };
    });
    ngApp.directive('runningButton', function() {
        return {
            restrict: 'EA',
            template: "<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
            scope: {
                isRunning: '='
            },
            replace: true,
            transclude: true
        };
    });
    ngApp.directive('flexImg', function() {
        return {
            restrict: 'A',
            replace: true,
            template: "<img src='{{img.imgSrc}}'>",
            link: function(scope, elem, attrs) {
                angular.element(elem).on('load', function() {
                    var w = this.clientWidth,
                        h = this.clientHeight,
                        sw, sh;
                    if (w > h) {
                        sw = w / h * 80;
                        angular.element(this).css({
                            'height': '100%',
                            'width': sw + 'px',
                            'top': '0',
                            'left': '50%',
                            'margin-left': (-1 * sw / 2) + 'px'
                        });
                    } else {
                        sh = h / w * 80;
                        angular.element(this).css({
                            'width': '100%',
                            'height': sh + 'px',
                            'left': '0',
                            'top': '50%',
                            'margin-top': (-1 * sh / 2) + 'px'
                        });
                    }
                })
            }
        }
    });
    ngApp.directive('tmsFilter', function() {
        return {
            restrict: 'A',
            link: function(scope, ele, attrs) {
                var $switch, $ok, $cancel, fnSelectItem, fnDefaultItem;
                fnDefaultItem = function() {
                    var defaultItem, path, i;
                    path = attrs.tmsFilterDefault;
                    path = path.split('.');
                    i = 0;
                    defaultItem = scope;
                    while (i < path.length) {
                        defaultItem = defaultItem[path[i]];
                        i++;
                    }
                    defaultItem && (defaultItem = scope.match(defaultItem));
                    return defaultItem;
                };
                fnSelectItem = function(item) {
                    if (!item) return;
                    var selected;
                    item._selected = !item._selected;
                    selected = scope.tmsFilter.selected;
                    if (item._selected) {
                        if (attrs.tmsFilterMultiple && attrs.tmsFilterMultiple === 'N') {
                            selected.length === 1 && (selected[0]._selected = false);
                            selected[0] = item;
                        } else {
                            selected.push(item);
                        }
                    } else {
                        selected.splice(selected.indexOf(item), 1);
                    }
                };
                scope.tmsFilter === undefined && (scope.tmsFilter = {});
                scope.tmsFilter.opened = false;
                scope.tmsFilter.selected = [];
                if (attrs.tmsFilterDefault && attrs.tmsFilterDefault.length && scope.match) {
                    if (scope.onDataReady) {
                        scope.onDataReady(function(rounds) {
                            fnSelectItem(fnDefaultItem());
                        });
                    } else {
                        fnSelectItem(fnDefaultItem());
                    }
                }
                scope.click = fnSelectItem;
                $switch = $(ele).find('[tms-filter-switch]').click(function() {
                    scope.$apply(function() {
                        scope.tmsFilter.opened = !scope.tmsFilter.opened;
                    });
                });
                $ok = $(ele).find('[tms-filter-ok]').click(function() {
                    scope.$apply(function() {
                        scope.tmsFilter.opened = false;
                        scope.$emit(attrs.tmsFilterEvent, scope.tmsFilter.selected);
                    });
                });
                $cancel = $(ele).find('[tms-filter-cancel]').click(function() {
                    scope.$apply(function() {
                        scope.tmsFilter.opened = false;
                    });
                });
            }
        };
    });
    ngApp.directive('enrollRecords', function() {
        return {
            restrict: 'A',
            replace: 'false',
            link: function(scope, ele, attrs) {
                if (attrs.enrollRecordsOwner && attrs.enrollRecordsOwner.length) {
                    scope.options.owner = attrs.enrollRecordsOwner;
                }
            }
        }
    });
});