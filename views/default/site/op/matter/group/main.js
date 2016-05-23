ngApp = angular.module('app', ['ngSanitize']);
ngApp.config(['$controllerProvider', function($cp) {
    ngApp.provider = {
        controller: $cp.register
    };
}]);
ngApp.filter("maskmobile", function() {
    return function(mobile) {
        if (mobile && mobile.length > 4) {
            var i, start = Math.round((mobile.length - 4) / 2);
            mobile = mobile.split('');
            for (i = 0; i < 4; i++)
                mobile[start + i] = '*';
            return mobile.join('');
        } else
            return '****';
    }
});
ngApp.directive('dynamicHtml', function($compile) {
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
var LS = (function(fields) {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(fields, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var j, l, url = '/rest/site/op/matter/group',
            _this = this,
            search = [];
        method && method.length && (url += '/' + method);
        if (arguments.length > 1) {
            for (i = 1, l = arguments.length; i < l; i++) {
                search.push(arguments[i] + '=' + _this.p[arguments[i]]);
            };
            url += '?' + search.join('&');
        }
        return url;
    };
    return {
        p: locationSearch(),
        j: j
    };
})(['site', 'app', 'rid']);
ngApp.controller('ctrl', ['$scope', '$http', '$q', function($scope, $http, $q) {
    $scope.getUsers = function(callback) {
        var deferred = $q.defer();
        $http.get(LS.j('usersGet', 'site', 'app', 'rid') + '&hasData=N').success(function(rsp) {
            $scope.players = rsp.data.players;
            $scope.winners = rsp.data.winners;
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    $scope.matched = function(candidate, target) {
        var k, v;
        if (!candidate) return false;
        if (Object.keys(target).length === 0) return true;
        for (k in target) {
            v = target[k];
            if (candidate.data[k] === v) return true;
        }
        return false;
    };
    /*清空结果*/
    $scope.empty = function(fromBegin) {
        $http.get(LS.j('empty', 'site', 'app')).success(function(rsp) {
            if (fromBegin && fromBegin === 'Y') {
                var url, t;
                t = (new Date()).getTime();
                url = '/rest/site/op/matter/group?site=' + LS.p.site + '&app=' + LS.p.app + '&_=' + t;
                location.href = url;
            } else {
                location.reload();
            }
        });
    };
    /*在指定轮次中添加选中的用户*/
    $scope.submit = function(winners) {
        var deferred = $q.defer(),
            url = LS.j('done', 'site', 'app'),
            posted = [];
        angular.forEach(winners, function(winner) {
            posted.push({
                uid: winner.userid,
                nickname: winner.nickname,
                ek: winner.enroll_key,
                rid: winner.round_id,
            });
        });
        $http.post(url, posted).success(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    $http.get(LS.j('pageGet', 'site', 'app')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        (function setPage(page) {
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
                                        $scope.page = page;
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
                    $scope.page = page;
                })();
            } else {
                $scope.page = page;
            }
        })(params.page);
    });
}]);