'use strict';
/**
 * directives:
 * combox
 * editable
 */
angular.module('ui.tms', ['ngSanitize']).
service('noticebox', ['$timeout', function($timeout) {
    var _boxId = 'tmsbox' + (new Date() * 1),
        _last = {
            type: '',
            timer: null
        },
        _getBox = function(type, msg) {
            var box;
            box = document.querySelector('#' + _boxId);
            if (box === null) {
                box = document.createElement('div');
                box.setAttribute('id', _boxId);
                box.classList.add('notice-box');
                box.classList.add('alert');
                box.classList.add('alert-' + type);
                box.innerHTML = '<div>' + msg + '</div>';
                document.body.appendChild(box);
                _last.type = type;
            } else {
                if (_last.type !== type) {
                    box.classList.remove('alert-' + type);
                    _last.type = type;
                }
                box.childNodes[0].innerHTML = msg;
            }

            return box;
        };

    this.close = function() {
        var box;
        box = document.querySelector('#' + _boxId);
        if (box) {
            document.body.removeChild(box);
        }
    };
    this.error = function(msg) {
        var box, btn;

        /*取消自动关闭*/
        if (_last.timer) {
            $timeout.cancel(_last.timer);
            _last.timer = null;
        }
        /*显示消息框*/
        box = _getBox('danger', msg);
        /*手工关闭*/
        btn = document.createElement('button');
        btn.classList.add('close');
        btn.innerHTML = '<span>&times;</span>';
        box.insertBefore(btn, box.childNodes[0]);
        btn.addEventListener('click', function() {
            document.body.removeChild(box);
        });
    };
    this.success = function(msg) {
        var box;
        /*取消自动关闭*/
        _last.timer && $timeout.cancel(_last.timer);
        /*显示消息框*/
        box = _getBox('success', msg);
        /*保持2秒钟后自动关闭*/
        _last.timer = $timeout(function() {
            if (box.parentNode && box.parentNode === document.body) {
                document.body.removeChild(box);
            }
            _last.timer = null;
        }, 2000);
    };
    this.info = function(msg) {
        var box;
        /*取消自动关闭*/
        _last.timer && $timeout.cancel(_last.timer);
        /*显示消息框*/
        box = _getBox('info', msg);
        /*保持2秒钟后自动关闭*/
        _last.timer = $timeout(function() {
            if (box.parentNode && box.parentNode === document.body) {
                document.body.removeChild(box);
            }
            _last.timer = null;
        }, 2000);
    };
    this.progress = function(msg) {
        /*显示消息框*/
        _getBox('progress', msg);
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
    $scope.gotoOne = function(e) {
        $scope.$emit($scope.evt + 'link', e, $scope.state);
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
            link: '@',
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
            elem[0].querySelector('.dropdown-toggle').addEventListener('click', function(e) {
                if (!this.classList.contains('open') && !scope.retainState) {
                    scope.empty();
                    scope.$apply();
                }
            });
            var menus = elem[0].querySelectorAll('.dropdown-menu *');
            for (var i = 0, ii = menus.length; i < ii; i++) {
                var elemMenu = menus[i];
                elemMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
    }
}).directive('tmsEditable', ['$timeout', function($timeout) {
    return {
        restrict: 'A',
        scope: {
            evtPrefix: '@',
            noRemove: '@',
            prop: '@',
            obj: '=',
            state: '@'
        },
        templateUrl: '/static/template/editable.html?_=4',
        link: function(scope, elem, attrs) {
            function whenBlur(event) {
                if (!event.relatedTarget || $(elem).find('.input-group button')[0] !== event.relatedTarget) {
                    delete scope.focus;
                    if (scope.obj[scope.prop] !== scope.editing.value) {
                        scope.obj[scope.prop] = scope.editing.value;
                        scope.valueChanged();
                    }
                    //if (scope.obj[scope.prop] && scope.obj[scope.prop].length === 0) {
                    //    scope.remove();
                    //}
                }
            }

            function onBlur(event) {
                var phase;
                phase = scope.$root.$$phase;
                if (phase === '$digest' || phase === '$apply') {
                    whenBlur(event);
                } else {
                    scope.$apply(function() {
                        whenBlur(event);
                    });
                }
            }
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
            scope.valueChanged = function() {
                scope.obj[scope.prop] = scope.editing.value;
                delete scope.focus;
                if (scope.evtPrefix && scope.evtPrefix.length) {
                    scope.$emit(scope.evtPrefix + '.xxt.editable.changed', scope.obj, scope.state);
                } else {
                    scope.$emit('xxt.editable.changed', scope.obj, scope.state);
                }
            };
            scope.submitChange = function(event) {
                event.preventDefault();
                event.stopPropagation();
                scope.valueChanged();
            };
            scope.remove = function(event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                if (scope.evtPrefix && scope.evtPrefix.length) {
                    scope.$emit(scope.evtPrefix + '.xxt.editable.remove', scope.obj, scope.state);
                } else {
                    scope.$emit('xxt.editable.remove', scope.obj, scope.state);
                }
            };
            scope.$on('xxt.editable.add', function(event, newObj) {
                if (newObj === scope.obj) {
                    scope.focus = true;
                }
            });
            scope.$watch('focus', function(nv, ov) {
                if (nv) {
                    $(elem).find('input').on('blur', onBlur).focus();
                }
            }, true);
            scope.editing = {
                value: scope.obj[scope.prop]
            };
        }
    }
}]).directive('tmsArrayCheckbox', ['$timeout', '$parse', function($timeout, $parse) {
    function fnFindCheckbox(elem, stack) {
        if (elem.nodeName === 'INPUT' && elem.getAttribute('type') === 'checkbox') {
            stack.push(elem);
            return;
        }
        if (elem.children && elem.children.length) {
            angular.forEach(elem.children, function(child) {
                fnFindCheckbox(child, stack);
            });
        }
        return;
    }

    return {
        restrict: 'A',
        link: function(scope, elems, attrs) {
            $timeout(function() {
                var checkboxStack = [],
                    oInnerModel = {};

                fnFindCheckbox(elems[0], checkboxStack);
                if (checkboxStack.length) {
                    scope.$watch(attrs.model, function(oOutModel) {
                        var aBeforeValue;
                        if (oOutModel) {
                            if (angular.isArray(oOutModel)) {
                                aBeforeValue = oOutModel;
                            } else if (angular.isString(oOutModel)) {
                                aBeforeValue = oOutModel.split(',');
                            } else if (angular.isObject(oOutModel)) {
                                angular.forEach(oOutModel, function(key, val) {
                                    val && aBeforeValue.push(key);
                                });
                            } else {
                                aBeforeValue = [];
                            }
                            if (aBeforeValue.length) {
                                checkboxStack.forEach(function(eleCheckbox) {
                                    var val;
                                    val = eleCheckbox.getAttribute('value');
                                    if (aBeforeValue && aBeforeValue.length) {
                                        if (aBeforeValue.indexOf(val) !== -1) {
                                            eleCheckbox.checked = true;
                                            oInnerModel[val] = true;
                                        }
                                    }
                                });
                            }
                        }
                    });
                    checkboxStack.forEach(function(eleCheckbox) {
                        eleCheckbox.addEventListener('change', function() {
                            var val;
                            val = this.getAttribute('value');
                            if (this.checked) {
                                oInnerModel[val] = true;
                            } else {
                                delete oInnerModel[val];
                            }
                            scope.$apply(function() {
                                $parse(attrs.model).assign(scope, Object.keys(oInnerModel));
                            });
                        });
                    });
                }
            });
        }
    };
}]).filter('tmsDateFilter', ['$filter', function($filter) {
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
}]).directive('tmsDatepicker', function() {
    var _version = 8;
    return {
        restrict: 'EA',
        scope: {
            date: '=tmsDate',
            defaultDate: '@tmsDefaultDate',
            mask: '@tmsMask', //y,m,d,h,i
            title: '@tmsTitle',
            titleAddon: '@tmsTitleAddon',
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
}).directive('tmsAutoUpdate', function() {
    var link = function(scope, element, attrs) {
        var fnPending = null;
        var onInput = function() {
            scope.tmsUpdate();
            scope.$root.$$phase === null && scope.$apply();
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
                    if (scope.evtPrefix && scope.evtPrefix.length) {
                        scope.$emit(scope.evtPrefix + '.orderChanged', movedObj, scope.state);
                    } else {
                        scope.$emit('orderChanged', movedObj, scope.state);
                    }
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
                    if (scope.evtPrefix && scope.evtPrefix.length) {
                        scope.$emit(scope.evtPrefix + '.orderChanged', movedObj, scope.state);
                    } else {
                        scope.$emit('orderChanged', movedObj, scope.state);
                    }
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
            evtPrefix: '@',
            state: '@'
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
            var repeatExpr, childExpr, rootExpr, childrenExpr, branchExpr;
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
                    var i = cache.length;
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
}).directive('tmsTableWrap', function() {
    return {
        restrict: 'A',
        scope: {
            minColWidth: '@',
            ready: '=',
            overflowX: '@'
        },
        link: function(scope, elem, attrs) {
            scope.$watch('ready', function(ready) {
                if (ready === 'Y') {
                    var eleWrap = elem[0],
                        eleTable = eleWrap.querySelector('table'),
                        minColWidth = scope.minColWidth || 120,
                        eleCols, tableWidth = 0;

                    if (eleTable) {
                        if (scope.overflowX) {
                            eleWrap.style.overflowX = scope.overflowX;
                        }
                        eleTable.style.maxWidth = 'none';
                        eleCols = eleTable.querySelectorAll('th');
                        angular.forEach(eleCols, function(eleCol) {
                            if (eleCol.style.width) {
                                tableWidth += parseInt(eleCol.style.width.replace('px', ''));
                            } else {
                                tableWidth += minColWidth;
                            }
                            eleTable.style.width = tableWidth + 'px';
                        });
                    }
                }
            });
        }
    }
}).directive('tmsFlexHeight', function() {
    return {
        restrict: 'A',
        scope: {
            top: '@',
            bottom: '@'
        },
        link: function(scope, elem, attrs) {
            var bodyHeight = document.documentElement.clientHeight;
            elem[0].style.height = (bodyHeight - scope.top - scope.bottom) + 'px';
            if (attrs.overflowY) {
                elem[0].style.overflowY = attrs.overflowY;
            } else {
                elem[0].style.overflowY = 'auto';
            }
        }
    }
}).directive('flexImg', function() {
    return {
        restrict: 'A',
        replace: true,
        template: "<img ng-src='{{img.imgSrc}}'>",
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
}).factory('facListFilter', ['$timeout', function($timeout) {
    var oFacFilter;
    oFacFilter = {
        keyword: '',
        target: null,
        init: function(fnCallbck, oOutside) {
            this.fnCallbck = fnCallbck;
            this.oOutside = oOutside || {};
            return this;
        },
        show: function(event) {
            var eleKw;
            this.target = event.target;
            while (this.target.tagName !== 'TH') {
                this.target = this.target.parentNode;
            }
            if (!this.target.dataset.filterBy) {
                alert('没有指定过滤字段【data-filter-by】');
                return;
            }
            this.keyword = this.oOutside.keyword || '';
            $(this.target).trigger('show');
            $timeout(function() {
                var el = document.querySelector('input[ng-model="filter.keyword"]');
                if (el && el.hasAttribute('autofocus')) {
                    el.focus();
                }
            }, 200);
        },
        close: function() {
            if (this.keyword) {
                this.target.classList.add('active');
            } else {
                this.target.classList.remove('active');
            }
            $(this.target).trigger('hide');
        },
        cancel: function() {
            this.oOutside.keyword = this.keyword = '';
            this.oOutside.by = '';
            this.close();
            this.fnCallbck && this.fnCallbck(this.oOutside);
        },
        exec: function() {
            this.oOutside.keyword = this.keyword;
            this.oOutside.by = this.keyword ? this.target.dataset.filterBy : '';
            this.fnCallbck && this.fnCallbck(this.oOutside);
            this.close();
        },
        keyUp: function(event) {
            if (event.keyCode == 13) {
                this.exec();
            }
        }
    };
    return oFacFilter;
}]);