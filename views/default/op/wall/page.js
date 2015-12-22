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
app = angular.module('app', ['ngSanitize']);
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
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.wallId = ls.match(/wall=([^&]*)/)[1];
    $scope.stop = false;
    $http.get('/rest/op/wall/pageGet?mpid=' + $scope.mpid + '&wall=' + $scope.wallId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.Wall = params.wall;
        setPage($scope, params.page);
        $http.get('/rest/op/wall/messageList?mpid=' + $scope.mpid + '&wall=' + $scope.wallId + '&_=' + (new Date()).getTime(), {
            headers: {
                'Accept': 'application/json'
            }
        }).success(function(rsp) {
            var last, worker;
            $scope.messages = rsp.data[0];
            last = rsp.data[1];
            worker = new Worker("/views/default/op/wall/wallMessages.js?_=2");
            worker.onmessage = function(e) {
                var messages = e.data;
                for (var i in messages) {
                    if (!inlist(messages[i].id))
                        $scope.messages.splice(0, 0, messages[i]);
                }
                $scope.$apply();
            };
            worker.postMessage({
                mpid: $scope.mpid,
                wid: $scope.wallId,
                last: last
            });
            $scope.stop = function() {
                worker.terminate();
            };
        });
    });
}]);