var setPage = function($scope, page) {
    if (page.ext_css && page.ext_css.length) {
        angular.forEach(page.ext_css, function(css) {
            var link, head;
            link = document.createElement('link');
            link.href = css.url;
            link.rel = 'stylesheet';
            head = document.querySelector('head');
            head.appendChild(link);
        });
    }
    if (page.ext_js && page.ext_js.length) {
        var i, l, loadJs;
        i = 0;
        l = page.ext_js.length;
        loadJs = function() {
            var js;
            js = page.ext_js[i];
            $.getScript(js.url, function() {
                i++;
                if (i === l) {
                    if (page.js && page.js.length) {
                        $scope.$apply(
                            function dynamicjs() {
                                eval(page.js);
                                $scope.Page = page;
                            }
                        );
                    }
                } else {
                    loadJs();
                }
            });
        };
        loadJs();
    } else if (page.js && page.js.length) {
        (function dynamicjs() {
            eval(page.js);
            $scope.Page = page;
        })();
    } else {
        $scope.Page = page;
    }
};
app = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'ui.tms']);
app.config(['$controllerProvider', '$uibTooltipProvider', function($cp, $uibTooltipProvider) {
    app.provider = {
        controller: $cp.register
    }
}]);
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
app.controller('wallCtrl', ['$scope', '$http', function($scope, $http) {
    var inlist = function(id) {
        for (var i in $scope.messages) {
            if ($scope.messages[i].id == id)
                return true;
        }
        return false;
    };
    var ls;
    ls = location.search;
    $scope.siteId = ls.match(/site=([^&]*)/)[1];
    $scope.wallId = ls.match(/wall=([^&]*)/)[1];
    $scope.stop = false;
    $http.get('/rest/site/op/matter/wall/pageGet?site=' + $scope.siteId + '&wall=' + $scope.wallId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.Wall = params.wall;
        if($scope.Wall.scenario =='interact') {
            if(!$scope.Wall.interact_matter || $scope.Wall.interact_matter.length == 0){
                alert("未指定分享素材");
                return;
            }
            if(!$scope.Wall.matters_img || $scope.Wall.matters_img.length == 0){
                alert("未指定分享素材的二维码");
                return;
            }
            if(!$scope.Wall.result_img || $scope.Wall.result_img.length == 0) {
                alert("未指定宝箱底图");
                return;
            }
            setPage($scope, params.page);
            $scope.$on('xxt.tms-datepicker.change', function(event, data) {
                $scope.Wall.timestamp = data.value;
            });
        }else{
            setPage($scope, params.page);
            $http.get('/rest/site/op/matter/wall/messageList?site=' + $scope.siteId + '&wall=' + $scope.wallId + '&_=' + (new Date() * 1), {
                headers: {
                    'Accept': 'application/json'
                }
            }).success(function(rsp) {
                var last, worker;
                $scope.messages = rsp.data[0];
                last = rsp.data[1];
                worker = new Worker("/views/default/site/op/matter/wall/wallMessages.js?_=2");
                worker.onmessage = function(e) {
                    var messages = e.data;
                    for (var i in messages) {
                        if (!inlist(messages[i].id))
                            $scope.messages.splice(0, 0, messages[i]);
                    }
                    $scope.$apply();
                };
                worker.postMessage({
                    siteId: $scope.siteId,
                    wid: $scope.wallId,
                    last: last
                });
                $scope.stop = function() {
                    worker.terminate();
                };
            });
        }
    });
}]);
