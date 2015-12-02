app.directive('tmsExec', ['$rootScope', '$timeout', function($rootScope, $timeout) {
    return {
        restrict: 'A',
        link: function(scope, elem, attrs) {
            $timeout(function() {
                if ($rootScope.$$phase) {
                    return scope.$eval(attrs.tmsExec);
                } else {
                    return scope.$apply(attrs.tmsExec);
                }
            }, 0);
        }
    };
}]);
app.directive('runningButton', function() {
    return {
        restrict: 'EA',
        template: "<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
        scope: {
            isRunning: '='
        },
        replace: true,
        transclude: true
    }
});
app.directive('flexImg', function() {
    return {
        restrict: 'A',
        replace: true,
        template: "<img src='{{img.imgSrc}}'>",
        link: function(scope, elem, attrs) {
            $(elem).on('load', function() {
                var w = $(this).width(),
                    h = $(this).height(),
                    sw, sh;
                if (w > h) {
                    sw = w / h * 72;
                    $(this).css({
                        'height': '100%',
                        'width': sw + 'px',
                        'top': '0',
                        'left': '50%',
                        'margin-left': (-1 * sw / 2) + 'px'
                    });
                } else {
                    sh = h / w * 72;
                    $(this).css({
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
app.directive('tmsFilter', function() {
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
app.directive('enrollRecords', function() {
    return {
        restrict: 'A',
        replace: 'false',
        link: function(scope, ele, attrs) {
            if (attrs.enrollRecordsOwner && attrs.enrollRecordsOwner.length) {
                scope.options.owner = attrs.enrollRecordsOwner;
            }
            scope.fetch();
        }
    }
});