'use strict';

var ngMod = angular.module('nav.ui.xxt', ['ui.bootstrap']);
ngMod.directive('tmsPopNav', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    var html;
    html = "<div class='tms-nav-target'>";
    html += "<div ng-repeat=\"nav in navs\"><button class='btn btn-default btn-block' ng-click=\"navTo($event,nav)\">{{nav.title}}</button></div>";
    html += '<div ng-if="custom" class=\"checkbox\"><label style=\"color:#000;\"" ng-click=\"setCustom($event)\"><input type=\"checkbox\" ng-model=\"custom.stopTip\" ng-click=\"setCustom($event)\"> 不再提示</label></div>';
    html += "</div>";
    $templateCache.put('popNavTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        transclude: true,
        scope: {
            navs: '=navs',
            custom: '=custom'
        },
        template: "<span><span ng-if=\"!navs||navs.length===0\" ng-transclude></span><span ng-if=\"navs.length\" uib-popover-template=\"'popNavTemplate.html'\" popover-placement=\"bottom\" popover-trigger=\"'show'\"><span ng-transclude></span><span class=\"caret\"></span></span></span>",
        link: function(scope, elem, attrs) {
            var elePopover, fnOpenPopover, fnClosePopover;
            fnOpenPopover = function() {
                var popoverEvt;
                elePopover = elem[0].children[0];
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('show', true, false);
                elePopover.dispatchEvent(popoverEvt);
            };
            fnClosePopover = function() {
                var popoverEvt;
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('hide', true, false);
                elePopover.dispatchEvent(popoverEvt);
                document.body.removeEventListener('click', fnClosePopover);
            };
            elem[0].addEventListener('click', function(event) {
                event.stopPropagation();
                event.preventDefault();
                fnOpenPopover();
                document.body.addEventListener('click', fnClosePopover);
            });
            scope.$watch('custom', function(nv) {
                if (nv && nv.stopTip === false) {
                    fnOpenPopover();
                    document.body.addEventListener('click', fnClosePopover);
                    if (attrs.closeAfter && parseInt(attrs.closeAfter)) {
                        $timeout(function() {
                            fnClosePopover();
                        }, attrs.closeAfter);
                    }
                }
            });
        },
        controller: ['$scope', function($scope) {
            $scope.setCustom = function($event, prop) {
                $event.stopPropagation();
            };
            $scope.navTo = function(event, oNav) {
                if (oNav.url) {
                    location.href = oNav.url;
                } else if ($scope.$parent.gotoNav) {
                    $scope.$parent.gotoNav(event, oNav);
                }
            };
        }]
    };
}]);