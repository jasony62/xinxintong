define(['angular', 'base'], function(angular, app) {
    "use strict";
    loadCss("/views/default/app/merchant/directive.css");
    var __util = {};
    __util.makeDialog = function(id, html) {
        var eleMask, dlg;
        dlg = "<div class='dialog dlg'>";
        html.header && html.header.length && (dlg += "<div class='dlg-header'>" + html.header + "</div>");
        dlg += "<div class='dlg-body'>" + html.body + "</div>";
        dlg += "<div class='dlg-footer'>" + html.footer + "</div>";
        dlg += "</div>";
        eleMask = document.createElement('div');
        eleMask.setAttribute('id', id);
        eleMask.classList.add('dialog');
        eleMask.classList.add('mask');
        eleMask.innerHTML = dlg;
        document.body.appendChild(eleMask);
        return angular.element(eleMask).contents();
    };
    app.directive('dynamicHtml', function($compile) {
        return {
            restrict: 'EA',
            replace: true,
            link: function(scope, ele, attrs) {
                scope.$watch(attrs.dynamicHtml, function(html) {
                    if (html && html.length) {
                        ele.html(html);
                        $compile(ele.contents())(scope);
                    }
                });
            }
        };
    });
    app.directive('dynaComponent', ['$compile', '$http', function($compile, $http) {
        return {
            restrict: 'EA',
            replace: true,
            compile: function(ele, attrs) {
                var html = ele.html();
                ele.html('');
                return {
                    post: function(scope, ele, attrs) {
                        scope.$watch(attrs.url, function(url) {
                            if (url && url.length) {
                                $http.get(url).success(function(rsp) {
                                    var component = rsp.data;
                                    if (component.css && component.css.length) {
                                        var style = document.createElement('style');
                                        style.type = 'text/css';
                                        style.innerHTML = component.css;
                                        document.querySelector('head').appendChild(style);
                                    }
                                    if (component.js && component.js.length) {
                                        (function loadjs() {
                                            eval(component.js);
                                        })();
                                    }
                                    if (component.html && component.html.length) {
                                        ele.html(component.html);
                                        $compile(ele.contents())(scope);
                                    } else {
                                        ele.html(html);
                                        $compile(ele.contents())(scope);
                                    }
                                });
                            } else {
                                ele.html(html);
                                $compile(ele.contents())(scope);
                            }
                        });
                    }
                }
            }
        };
    }]);
    app.directive('tmsDate', ['$compile', function($compile) {
        return {
            restrict: 'A',
            scope: {
                value: '=tmsDateValue',
                items: '@tmsDateItems',
                autoNow: '@tmsDateAutoNow'
            },
            controller: function($scope) {
                $scope.close = function() {
                    $scope.opened = false;
                    var dlg = document.querySelector('#' + $scope.dialogID);
                    dlg.parentNode.removeChild(dlg);
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
                scope.items === undefined && (scope.items = 'yMdHm');
                scope.options = {
                    years: [2014, 2015, 2016, 2017],
                    months: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                    dates: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31],
                    hours: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
                    minutes: [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55],
                };
                scope.$watch('value', function(nv) {
                    if (!nv) {
                        nv = new Date().getTime();
                        if (scope.autoNow && scope.autoNow === 'Y') {
                            scope.value = nv;
                        }
                    };
                    dtObject = new Date();
                    dtObject.setTime(nv);
                    dtMinute = Math.round(dtObject.getMinutes() / 5) * 5;
                    scope.data = {
                        year: dtObject.getFullYear(),
                        month: dtObject.getMonth() + 1,
                        date: dtObject.getDate(),
                        hour: dtObject.getHours(),
                        minute: dtMinute
                    };
                    scope.options.minutes.indexOf(dtMinute) === -1 && scope.options.minutes.push(dtMinute);
                });
                htmlBody = '';
                scope.items.indexOf('y') !== -1 && (htmlBody = '<div class="form-group"><select class="form-control" ng-model="data.year" ng-options="y for y in options.years"></select></div>');
                scope.items.indexOf('M') !== -1 && (htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.month" ng-options="m for m in options.months"></select></div>');
                scope.items.indexOf('d') !== -1 && (htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.date" ng-options="d for d in options.dates"></select></div>');
                scope.items.indexOf('H') !== -1 && (htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.hour" ng-options="h for h in options.hours"></select></div>');
                scope.items.indexOf('m') !== -1 && (htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.minute" ng-options="mi for mi in options.minutes"></select></div>');
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
                elem[0].querySelector('[ng-bind]').addEventListener('click', fnOpenPicker, false);
            }
        }
    }]);
    app.directive('tmsTime', ['$compile', function($compile) {
        var format = function(timePoint) {
            var h, m;
            h = Math.floor(timePoint / 60);
            m = timePoint - (h * 60);
            m < 10 && (m = '0' + m);
            return h + ':' + m;
        };
        var choosedTime = {
            begin: null,
            end: null
        };
        return {
            restrict: 'A',
            scope: {
                value: '=tmsTimeValue',
                begin: '@tmsTimeBegin',
                end: '@tmsTimeEnd',
                interval: '@tmsTimeInterval'
            },
            template: '<span ng-repeat="t in timePoints" ng-bind="t.l" ng-class="{\'selected\':t.selected}" ng-click="chooseTime(t)"></span>',
            controller: function($scope) {
                $scope.chooseTime = function(time) {
                    time.selected = !time.selected;
                    if (choosedTime.begin === time) {
                        choosedTime.begin = choosedTime.end;
                        choosedTime.end = null;
                    } else if (choosedTime.end === time) {
                        choosedTime.end = null;
                    } else if (choosedTime.begin === null) {
                        choosedTime.begin = time;
                    } else if (choosedTime.end === null) {
                        if (time.v > choosedTime.begin.v) {
                            choosedTime.end = time;
                        } else {
                            choosedTime.end = choosedTime.begin;
                            choosedTime.begin = time;
                        }
                    } else {
                        if (time.v < choosedTime.begin.v) {
                            choosedTime.begin.selected = false;
                            choosedTime.begin = time;
                        } else if (time.v > choosedTime.end.v) {
                            choosedTime.end.selected = false;
                            choosedTime.end = time;
                        } else {
                            if (time.v - choosedTime.begin.v < choosedTime.end.v - time.v) {
                                choosedTime.begin.selected = false;
                                choosedTime.begin = time;
                            } else {
                                choosedTime.end.selected = false;
                                choosedTime.end = time;
                            }
                        }
                    }
                    $scope.value.begin = choosedTime.begin !== null ? choosedTime.begin.v : null;
                    $scope.value.end = choosedTime.end !== null ? choosedTime.end.v : null;
                    $scope._valueChanged = true;
                };
            },
            link: function(scope, elem, attrs) {
                var timePoint, endPoint, timePoints, timeSeg;
                timePoints = [];
                timePoint = scope.begin * 60;
                endPoint = scope.end * 60;
                while (timePoint <= endPoint) {
                    timeSeg = {
                        v: timePoint * 60 * 1000,
                        l: format(timePoint)
                    };
                    timePoints.push(timeSeg);
                    timePoint += parseInt(scope.interval);
                }
                scope.timePoints = timePoints;
                scope.$watch('value', function(nv) {
                    if (scope._valueChanged !== true) {
                        if (nv) {
                            angular.forEach(timePoints, function(tp) {
                                if (nv.begin !== undefined && tp.v === nv.begin && true !== tp.selected) {
                                    tp.selected = true;
                                    choosedTime.begin = tp;
                                } else if (nv.end !== undefined && tp.v === nv.end && true !== tp.selected) {
                                    tp.selected = true;
                                    choosedTime.end = tp;
                                }
                            });
                        }
                    } else {
                        scope._valueChanged = false;
                    }
                }, true);
            }
        }
    }]);
    app.directive('tmsLock', function() {
        return {
            restrict: 'A',
            scope: {
                lock: '=tmsLock'
            },
            priority: 99,
            compile: function(tElem, tAttrs) {
                var originalFn, lockableFn;
                if (tAttrs.tmsLockPromoter === 'Y' && tAttrs.ngClick) {
                    originalFn = tAttrs.ngClick;
                    lockableFn = '__lockable__' + originalFn;
                    tAttrs.ngClick = lockableFn;
                }
                return {
                    pre: function(scope, iElem, iAttrs) {
                        if (lockableFn) {
                            scope.$parent[lockableFn.replace(/\(.*\)/, '')] = function() {
                                var eleIndicator = document.createElement('div');
                                eleIndicator.classList.add('indicator');
                                scope.lock = true;
                                iElem.addClass('tms-lock-running');
                                iElem.append(eleIndicator);
                                scope.$parent[originalFn.replace(/\(.*\)/, '')].apply(scope, arguments).then(function() {
                                    scope.lock = false;
                                    iElem.removeClass('tms-lock-running');
                                    iElem[0].removeChild(eleIndicator);
                                });
                            };
                        }
                        scope.$watch('lock', function(locked) {
                            if (locked === true) {
                                iElem.addClass('tms-locked');
                                iAttrs.$set('disabled', true);
                            } else if (locked === false) {
                                iElem.removeClass('tms-locked');
                                iAttrs.$set('disabled', undefined);
                            }
                        });
                    }
                }
            }
        }
    });
});