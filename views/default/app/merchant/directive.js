var __util = {};
__util.makeDialog = function(id, html) {
    var dlg, $dlg;
    dlg = "<div class='dialog mask'><div class='dialog dlg'>";
    html.header && html.header.length && (dlg += "<div class='dlg-header'>" + html.header + "</div>");
    dlg += "<div class='dlg-body'>" + html.body + "</div>";
    dlg += "<div class='dlg-footer'>" + html.footer + "</div>";
    dlg += "</div></div>";
    $dlg = $(dlg).attr('id', id);
    $('body').append($dlg);
    return $dlg.contents();
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
                                component = rsp.data;
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
app.directive('tmsDatetime', ['$compile', function($compile) {
    return {
        restrict: 'A',
        scope: {
            value: '=tmsDatetimeValue',
            items: '@tmsDatetimeItems',
            autoNow: '@tmsDatetimeAutoNow'
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
            $(elem).find('[ng-bind]').click(fnOpenPicker);
        }
    }
}]);