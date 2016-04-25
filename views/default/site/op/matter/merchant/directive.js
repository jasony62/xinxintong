define(['angular', 'base'], function(angular, app) {
    "use strict";
    loadCss("/views/default/site/op/matter/merchant/directive.css");
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