angular.module('ui.tms', ['ngSanitize']).service('http2', ['$rootScope', '$http', '$sce', function($rootScope, $http, $sce) {
    this.get = function(url, callback, options) {
        options = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'autoBreak': true,
            'autoNotice': true,
        }, options);
        $http.get(url, options).success(function(rsp) {
            if (angular.isString(rsp)) {
                if (options.autoNotice) $rootScope.errmsg = $sce.trustAsHtml(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                if (options.autoNotice) $rootScope.errmsg = $sce.trustAsHtml(rsp.err_msg);
                if (options.autoBreak) return;
            }
            if (callback) callback(rsp);
        }).error(function(data, status) {
            $rootScope.errmsg = $sce.trustAsHtml(data);
        });
    };
    this.post = function(url, posted, callback, options) {
        options = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'autoBreak': true,
            'autoNotice': true,
        }, options);
        $http.post(url, posted, options).success(function(rsp) {
            if (angular.isString(rsp)) {
                if (options.autoNotice) $rootScope.errmsg = $sce.trustAsHtml(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                if (options.autoNotice) $rootScope.errmsg = $sce.trustAsHtml(rsp.err_msg);
                if (options.autoBreak) return;
            }
            if (callback) callback(rsp);
        }).error(function(data, status) {
            $rootScope.errmsg = $sce.trustAsHtml(data);
        });
    };
}]).controller('ComboxController', ['$scope', function($scope) {
    $scope.aChecked = [];
    if ($scope.evtPrefix === undefined)
        $scope.evt = 'xxt.combox.';
    else
        $scope.evt = $scope.evtPrefix + '.xxt.combox.';
    $scope.toggle = function(o) {
        var i = $scope.aChecked.indexOf(o);
        if (i !== -1) {
            $scope.aChecked.splice(i, 1);
        } else {
            $scope.aChecked.push(o);
        }
    };
    $scope.empty = function() {
        $scope.aChecked = [];
    };
    $scope.done = function(event) {
        if (event && event.target)
            $(event.target).parents('.dropdown-menu').dropdown('toggle');
        $scope.$emit($scope.evt + 'done', $scope.aChecked, $scope.state);
    };
    $scope.keydown = function(event) {
        switch (event.which) {
            case 32: //white space
            case 188: //','
                var val = $scope.input;
                if (val && val.length > 0) {
                    event.preventDefault();
                    $scope.input = '';
                    $scope.$emit($scope.evt + 'add', val, $scope.state);
                }
                break;
            case 8: //'backspace'
                var val = $scope.input;
                if (!val || val.length == 0) {
                    event.preventDefault();
                }
                break;
        }
    };
    $scope.blur = function(event) {
        var val = $scope.input;
        if (val && val.length > 0) {
            $scope.input = '';
            $scope.$emit($scope.evt + 'add', val, $scope.state);
        }
    };
    $scope.removeOne = function(e) {
        $scope.$emit($scope.evt + 'del', e, $scope.state);
    };
}]).directive('combox', function() {
    return {
        restrict: 'EA',
        scope: {
            disabled: '@',
            readonly: '@',
            retainState: '@',
            evtPrefix: '@',
            prop: '@',
            existing: '=',
            options: '=',
            state: '@'
        },
        controller: 'ComboxController',
        templateUrl: function() {
            return '/static/template/combox.html?_=2';
        },
        replace: true,
        link: function(scope, elem, attrs) {
            $(elem).find('.dropdown-toggle').click(function(e) {
                if (!$(this).parent().hasClass('open') && !scope.retainState) {
                    scope.empty();
                    scope.$apply();
                }
            });
            $(elem).find('.dropdown-menu *').click(function(e) {
                e.stopPropagation();
            });
        }
    }
}).directive('editable', ['$timeout', function($timeout) {
    return {
        restrict: 'A',
        scope: {
            prop: '@',
            obj: '='
        },
        templateUrl: '/static/template/editable.html?_=1',
        link: function(scope, elem, attrs) {
            var onBlur = function() {
                delete scope.focus;
                scope.$apply();
                if (scope.obj[scope.prop].length == 0)
                    scope.remove();
                else if (scope.oldVal !== scope.obj[scope.prop])
                    scope.$emit('xxt.editable.changed', scope.obj);
            };
            $(elem).on('click', function(event) {
                delete scope.enter;
                scope.focus = true;
                scope.$apply();
            }).mouseenter(function(event) {
                if (!scope.focus) {
                    scope.enter = true;
                    scope.$apply();
                }
            }).mouseleave(function(event) {
                delete scope.enter;
                scope.$apply();
            });
            scope.remove = function(event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                scope.$emit('xxt.editable.remove', scope.obj);
            };
            scope.$on('xxt.editable.add', function(event, newObj) {
                if (newObj === scope.obj)
                    scope.focus = true;
            });
            scope.$watch('focus', function(nv, ov) {
                if (nv) {
                    scope.oldVal = scope.obj[scope.prop];
                    $(elem).find('input').on('blur', onBlur).focus();
                }
            }, true);
        }
    }
}]).directive('noticeBox', ['$timeout', function($timeout) {
    return {
        restrict: 'EA',
        scope: {
            err: '=',
            info: '=',
            prog: '=',
            delay: '@'
        },
        templateUrl: '/static/template/noticebox.html?_=6',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            $scope.closeBox = function() {
                var msgType = '';
                if ($scope.err && $scope.err.toString().length) {
                    $scope.err = '';
                    msgType = 'err';
                } else if ($scope.info && $scope.info.length) {
                    $scope.info = '';
                    msgType = 'info';
                } else if ($scope.prog && $scope.prog.length) {
                    $scope.prog = '';
                    msgType = 'prog';
                }
                $scope.$emit('xxt.notice-box.timeout', msgType);
            };
            $scope.$watch('info', function(nv) {
                if (nv && nv.length > 0) {
                    $scope.err = $scope.prog = '';
                    $timeout(function() {
                        $scope.info = '';
                        $scope.$emit('xxt.notice-box.timeout', 'info');
                    }, $scope.delay || 2000);
                }
            });
            $scope.$watch('err', function(nv) {
                if (nv && nv.length > 0) {
                    $scope.prog && ($scope.prog = '');
                    $scope.info && ($scope.info = '');
                }
            });
            $scope.$watch('prog', function(nv) {
                if (nv && nv.length > 0) {
                    $scope.err && ($scope.err = '');
                    $scope.info && ($scope.info = '');
                }
            });
        }],
        replace: true
    };
}]).directive('tmsDatepicker', function() {
    return {
        restrict: 'EA',
        scope: {
            date: '=tmsDate',
            title: '@tmsTitle',
            state: '@tmsState',
            obj: '=tmsObj'
        },
        templateUrl: '/static/template/datepicker.html?_=2',
        controller: ['$scope', '$uibModal', function($scope, $uibModal) {
            $scope.open = function() {
                $uibModal.open({
                    templateUrl: 'tmsModalDatepicker.html',
                    controller: ['$scope', '$uibModalInstance', 'date', function($scope, $mi, date) {
                        date = (function() {
                            var d = new Date();
                            d.setTime(date == 0 ? d.getTime() : date * 1000);
                            return d;
                        })();
                        $scope.years = [2015, 2016, 2017];
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
                    size: 'sm',
                    resolve: {
                        date: function() {
                            return $scope.date;
                        }
                    }
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
}).directive('tmsAutoUpdate', function() {
    var link = function(scope, element, attrs) {
        var fnPending = null;
        var onInput = function() {
            scope.tmsUpdate();
        };
        element.on('input', function() {
            fnPending && clearTimeout(fnPending);
            fnPending = setTimeout(onInput, scope.tmsWait);
        });
    };
    return {
        scope: {
            tmsWait: '@',
            tmsUpdate: '&'
        },
        link: link,
    };
}).directive('dndList', function() {
    var link = function(scope, element, attrs) {
        var dndableOffset = attrs.dndableOffset || 0,
            connectWith = attrs.connectWith,
            savedNodes;
        var dndstart = function(event, ui) {
            ui.item.sortable = {
                index: ui.item.index(),
                cancel: function() {
                    ui.item.sortable._isCanceled = true;
                },
                isCanceled: function() {
                    return ui.item.sortable._isCanceled;
                },
                _isCanceled: false
            };
        };
        var dndactivate = function() {
            savedNodes = element.contents();
            var placeholder = element.sortable('option', 'placeholder');
            if (placeholder && placeholder.element && typeof placeholder.element === 'function') {
                var phElement = placeholder.element();
                phElement = angular.element(phElement);
                var excludes = element.find('[class="' + phElement.attr('class') + '"]');
                savedNodes = savedNodes.not(excludes);
            }
        };
        var dndupdate = function(event, ui) {
            if (!ui.item.sortable.received) {
                ui.item.sortable.dropindex = ui.item.index();
                ui.item.sortable.droptarget = ui.item.parent();
                element.sortable('cancel');
            }
            if (element.sortable('option', 'helper') === 'clone') {
                savedNodes = savedNodes.not(savedNodes.last());
            }
            savedNodes.appendTo(element);
            if (ui.item.sortable.received && !ui.item.sortable.isCanceled()) {
                scope.$apply(function() {
                    scope.dataset.splice(ui.item.sortable.dropindex - dndableOffset, 0, ui.item.sortable.moved);
                    scope.$emit('orderChanged', ui.item.sortable.moved);
                });
            }
        };
        var dndremove = function(event, ui) {
            if (!ui.item.sortable.isCanceled()) {
                scope.$apply(function() {
                    ui.item.sortable.moved = scope.dataset.splice(ui.item.sortable.index - dndableOffset, 1)[0];
                });
            }
        };
        var dndreceive = function(event, ui) {
            ui.item.sortable.received = true;
        };
        var dndstop = function(event, ui) {
            if (!ui.item.sortable.received && ('dropindex' in ui.item.sortable) && !ui.item.sortable.isCanceled()) {
                scope.$apply(function() {
                    var movedObj = scope.dataset[ui.item.sortable.index - dndableOffset];
                    scope.dataset.splice(
                        ui.item.sortable.dropindex - dndableOffset, 0,
                        scope.dataset.splice(ui.item.sortable.index - dndableOffset, 1)[0]
                    );
                    scope.$emit('orderChanged', movedObj);
                });
            } else {
                if ((!('dropindex' in ui.item.sortable) || ui.item.sortable.isCanceled()) && element.sortable('option', 'helper') !== 'clone') {
                    savedNodes.appendTo(element);
                }
            }
        };
        var ops = {
            items: '> .dndable',
            start: dndstart,
            activate: dndactivate,
            update: dndupdate,
            stop: dndstop,
            remove: dndremove,
            receive: dndreceive,
            axis: 'y'
        };
        if (connectWith) {
            element.sortable(angular.extend({
                connectWith: connectWith
            }, ops));
        } else {
            element.sortable(ops);
        }
    };
    return {
        scope: {
            dataset: '=',
        },
        link: link,
    };
}).directive('tmsTree', function() {
    return {
        restrict: 'A',
        transclude: 'element',
        priority: 1000,
        terminal: true,
        compile: function(tElement, tAttrs, transclude) {
            var repeatExpr, childExpr, rootExpr, childrenExpr;
            repeatExpr = tAttrs.tmsTree.match(/^(.*) in ((?:.*\.)?(.*)) at (.*)$/);
            childExpr = repeatExpr[1];
            rootExpr = repeatExpr[2];
            childrenExpr = repeatExpr[3];
            branchExpr = repeatExpr[4];
            return function link(scope, element, attrs) {
                var rootElement = element[0].parentNode,
                    cache = [];
                // Reverse lookup object to avoid re-rendering elements
                function lookup(child) {
                    var i = cache.length;
                    while (i--)
                        if (cache[i].scope[childExpr] === child)
                            return cache.splice(i, 1)[0];
                }
                scope.$watch(rootExpr, function(root) {
                    var currentCache = [];
                    // Recurse the data structure
                    (function walk(children, parentNode, parentScope, depth) {
                        if (children === undefined) console.log('error:' + rootExpr);
                        var i = 0,
                            n = children.length,
                            last = n - 1,
                            cursor,
                            child,
                            cached,
                            childScope,
                            grandchildren;
                        // Iterate the children at the current level
                        for (; i < n; ++i) {
                            // We will compare the cached element to the element in 
                            // at the destination index. If it does not match, then 
                            // the cached element is being moved into this position.
                            cursor = parentNode.childNodes[i];
                            child = children[i];
                            // See if this child has been previously rendered
                            // using a reverse lookup by object reference
                            cached = lookup(child);
                            // If the parentScope no longer matches, we've moved.
                            // We'll have to transclude again so that scopes 
                            // and controllers are properly inherited
                            if (cached && cached.parentScope !== parentScope) {
                                cache.push(cached);
                                cached = null;
                            }
                            // If it has not, render a new element and prepare its scope
                            // We also cache a reference to its branch node which will
                            // be used as the parentNode in the next level of recursion
                            if (!cached) {
                                transclude(parentScope.$new(), function(clone, childScope) {
                                    childScope[childExpr] = child;
                                    cached = {
                                        scope: childScope,
                                        parentScope: parentScope,
                                        element: clone[0],
                                        branch: clone.find(branchExpr)[0]
                                    };
                                    // This had to happen during transclusion so inherited 
                                    // controllers, among other things, work properly
                                    parentNode.insertBefore(cached.element, cursor);
                                });
                            } else if (cached.element !== cursor) {
                                parentNode.insertBefore(cached.element, cursor);
                            }
                            // Lets's set some scope values
                            childScope = cached.scope;
                            // Store the current depth on the scope in case you want 
                            // to use it (for good or evil, no judgment).
                            childScope.$depth = depth;
                            // Emulate some ng-repeat values
                            childScope.$index = i;
                            childScope.$first = (i === 0);
                            childScope.$last = (i === last);
                            childScope.$middle = !(childScope.$first || childScope.$last);
                            // Push the object onto the new cache which will replace
                            // the old cache at the end of the walk.
                            currentCache.push(cached);
                            // If the child has children of its own, recurse 'em.             
                            grandchildren = child[childrenExpr];
                            if (grandchildren && grandchildren.length) {
                                walk(grandchildren, cached.branch, childScope, depth + 1);
                            }
                        }
                    })(root, rootElement, scope, 0);
                    // Cleanup objects which have been removed.
                    // Remove DOM elements and destroy scopes to prevent memory leaks.
                    i = cache.length;
                    while (i--) {
                        cached = cache[i];
                        if (cached.scope)
                            cached.scope.$destroy();
                        if (cached.element)
                            cached.element.parentNode.removeChild(cached.element);
                    }
                    // Replace previous cache.
                    cache = currentCache;
                }, true);
            };
        }
    };
}).directive('runningButton', function() {
    return {
        restrict: 'EA',
        template: "<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
        scope: {
            isRunning: '='
        },
        replace: true,
        transclude: true
    }
}).directive('tmsAutoFocus', function($timeout) {
    return {
        restrict: 'A',
        link: function(_scope, _element) {
            $timeout(function() {
                _element[0].focus();
            });
        }
    };
});