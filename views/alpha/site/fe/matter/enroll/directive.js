'use strict';

window.__util = {};
window.__util.makeDialog = function(id, html) {
    var dlg, mask;

    mask = document.createElement('div');
    mask.setAttribute('id', id);
    mask.classList.add('dialog', 'mask');

    dlg = "<div class='dialog dlg'>";
    html.header && html.header.length && (dlg += "<div class='dlg-header'>" + html.header + "</div>");
    dlg += "<div class='dlg-body'>" + html.body + "</div>";
    html.footer && html.footer.length && (dlg += "<div class='dlg-footer'>" + html.footer + "</div>");
    dlg += "</div>";

    mask.innerHTML = dlg;

    document.body.appendChild(mask);

    return mask.children;
};

var ngMod = angular.module('directive.enroll', []);
ngMod.directive('tmsAppNav', ['$templateCache', function($templateCache) {
    var html;
    html = "<div class='tms-nav-target'>";
    html += "<div ng-if=\"navs.repos\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'repos')\">共享页</button></div>";
    html += "<div ng-if=\"navs.favor\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'favor')\">收藏页</button></div>";
    html += "<div ng-if=\"navs.rank\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'rank')\">排行页</button></div>";
    html += "<div ng-if=\"navs.votes\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'votes')\">投票榜</button></div>";
    html += "<div ng-if=\"navs.event\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'event')\">动态页<span class='notice-count' ng-if=\"noticeCount\" ng-bind=\"noticeCount\"></span></button></div>";
    html += "</div>";
    $templateCache.put('appNavTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        transclude: true,
        scope: {
            navs: '=appNavs',
            noticeCount: '=noticeCount'
        },
        template: "<span><span ng-if=\"!navs\"><span ng-transclude></span></span><span ng-if=\"navs\" uib-popover-template=\"'appNavTemplate.html'\" popover-placement=\"bottom\" popover-trigger=\"'outsideClick'\"><span ng-transclude></span><span class='notice-count' ng-if=\"noticeCount\" ng-bind=\"noticeCount\"></span><span class=\"caret\"></span></span></span>",
        controller: ['$scope', function($scope) {
            $scope.goto = function(event, page) {
                $scope.$parent.gotoPage(event, page);
            };
        }]
    };
}]);
ngMod.directive('tmsAppAct', ['$templateCache', function($templateCache) {
    var html;
    html = "<div class='tms-act-popover-wrap'>";
    html += "<div ng-if=\"acts.mockAsMember\"><button class='btn btn-default' ng-click=\"goto($event,'mockAsMember')\"><span ng-if=\"mocker.role!=='member'\">作为</span><span ng-if=\"mocker.role==='member'\">退出</span>成员</button></div>";
    html += "<div ng-if=\"acts.mockAsVisitor\"><button class='btn btn-default' ng-click=\"goto($event,'mockAsVisitor')\"><span ng-if=\"mocker.role!=='visitor'\">作为</span><span ng-if=\"mocker.role==='visitor'\">退出</span>访客</button></div>";
    html += "<div ng-if=\"acts.addRecord\"><button class='btn btn-default' ng-click=\"goto($event,'addRecord')\">添加记录</button></div>";
    html += "<div ng-if=\"acts.save\"><button class='btn btn-default' ng-click=\"goto($event,'save')\">保存</button></div>";
    html += "</div>";
    $templateCache.put('appActTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        scope: {
            acts: '=appActs'
        },
        template: "<button uib-popover-template=\"'appActTemplate.html'\" popover-placement=\"top-right\" popover-trigger=\"'outsideClick'\" popover-append-to-body=\"true\" class=\"tms-act-toggle\" popover-class=\"tms-act-popover\"><span class='glyphicon glyphicon-option-vertical'></span></button>",
        controller: ['$scope', function($scope) {
            $scope.$watch('acts', function(oActs) {
                var oMockAct;
                if (oActs) {
                    if (oMockAct = oActs.mockAsVisitor || oActs.mockAsMember) {
                        if (oMockAct.mocker && angular.isString(oMockAct.mocker)) {
                            if ($scope.mocker = $scope.$parent[oMockAct.mocker]) {
                                $scope.$parent.$watch(oMockAct.mocker, function(nv) {
                                    $scope.mocker = nv;
                                }, true);
                            }
                        }
                    }
                }
            });
            $scope.back = function() {
                history.back();
            };
            $scope.historyLen = function() {
                return history.length;
            };
            $scope.goto = function(event, page) {
                switch (page) {
                    case 'mockAsVisitor':
                        $scope.$parent.mockAsVisitor(event, $scope.mocker.role !== 'visitor');
                        break;
                    case 'mockAsMember':
                        $scope.$parent.mockAsMember(event, $scope.mocker.role !== 'member');
                        break;
                    case 'addRecord':
                        $scope.$parent.addRecord(event);
                        break;
                    case 'save':
                        $scope.$parent.save();
                        break;
                    default:
                        $scope.$parent.gotoPage(event, page);
                }
            };
        }]
    };
}]);
ngMod.directive('tmsDate', ['$compile', function($compile) {
    return {
        restrict: 'A',
        scope: {
            value: '=tmsDateValue'
        },
        controller: ['$scope', function($scope) {
            $scope.close = function() {
                var mask;
                mask = document.querySelector('#' + $scope.dialogID);
                document.body.removeChild(mask);
                $scope.opened = false;
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
                $scope.value = parseInt(dtObject.getTime() / 1000);
                $scope.close();
            };
        }],
        link: function(scope, elem, attrs) {
            var fnOpenPicker, dtObject, dtMinute, htmlBody;
            scope.value === undefined && (scope.value = (new Date() * 1) / 1000);
            dtObject = new Date();
            dtObject.setTime(scope.value * 1000);
            scope.options = {
                years: [2014, 2015, 2016, 2017, 2018, 2019, 2020],
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
                id = '_dlg-' + (new Date() * 1);
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
            elem[0].querySelector('[ng-bind]').addEventListener('click', fnOpenPicker);
        }
    }
}]);
ngMod.directive('flexImg', function() {
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
});
/**
 * 根据父元素的高度决定是否隐藏
 */
ngMod.directive('tmsHideParentHeight', function() {
    return {
        restrict: 'A',
        link: function(scope, elems, attrs) {
            var heightLimit, elem;
            if (attrs.tmsHideParentHeight) {
                heightLimit = attrs.tmsHideParentHeight;
                for (var i = 0, ii = elems.length; i < ii; i++) {
                    elem = elems[i];
                    if (elem.parentElement) {
                        window.addEventListener('resize', function() {
                            elem.classList.toggle('hidden', elem.parentElement.clientHeight < heightLimit);
                        });
                    }
                }
            }
        }
    }
});
/**
 * 监听元素的滚动事件并做出相应
 */
ngMod.directive('tmsScrollSpy', function() {
    return {
        restrict: 'A',
        scope: {
            selector: '@selector',
            offset: '@',
            onbottom: '&',
            toggleSpy: '='
        },
        link: function(scope, elems, attrs) {
            if (scope.selector === 'window') {
                window.addEventListener('scroll', function(event) {
                    var eleScrolling;
                    if (eleScrolling = event.target.scrollingElement) {
                        if (scope.toggleSpy) {
                            if (scope.onbottom && angular.isFunction(scope.onbottom)) {
                                if (eleScrolling.clientHeight + eleScrolling.scrollTop + parseInt(scope.offset) >= eleScrolling.scrollHeight) {
                                    scope.$apply(function() {
                                        scope.toggleSpy = false;
                                        scope.onbottom();
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }
    }
});