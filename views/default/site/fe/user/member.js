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
        var j, l, url = '/rest/site/fe/user/member',
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
})(['site', 'schema']);
app = angular.module('app', []);
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
app.controller('ctrlAuth', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var validate = function() {
        var required = function(value, len, alerttext) {
            if (value == null || value == "" || value.length < len) {
                $scope.errmsg = alerttext;
                return false;
            } else
                return true;
        };
        var isMobile = function(value, alerttext) {
            if (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) {
                $scope.errmsg = alerttext;
                return false;
            } else {
                return true;
            }
        };
        var isEmail = function(value, alerttext) {
            if (value === undefined) {
                $scope.errmsg = alerttext;
                return false;
            }
            var apos = value.indexOf("@"),
                dotpos = value.lastIndexOf(".");
            if (apos < 1 || dotpos - apos < 2) {
                $scope.errmsg = alerttext;
                return false;
            } else {
                return true;
            }
        };
        var member = $scope.member;
        if (member.name !== undefined && false === required(member.name, 2, '请提供您的姓名！')) {
            $('[ng-model="member.name"]').focus();
            return false;
        }
        if (member.mobile !== undefined && false === isMobile(member.mobile, '请提供正确的手机号（11位数字）！')) {
            $('[ng-model="member.mobile"]').focus();
            return false;
        }
        if (member.email !== undefined && false === isEmail(member.email, '请提供正确的邮箱！')) {
            $('[ng-model="member.email"]').focus();
            return false;
        }
        return true;
    };
    var sendRequest = function(url) {
        $scope.posting = true;
        $http.post(url, $scope.member).
        success(function(rsp) {
            $scope.posting = false;
            if (angular.isString(rsp)) {
                $scope.errmsg = rsp;
                return;
            }
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            window.parent && window.parent.onClosePlugin && window.parent.onClosePlugin();
        });
    };
    var setMember = function(user) {
        var member;
        user.members && (member = user.members[LS.p.schema]);
        $scope.member = {
            schema_id: LS.p.schema
        };
        if (member) {
            $scope.member.id = member.id;
            $scope.member.name = member.name;
            $scope.member.email = member.email;
            $scope.member.mobile = member.mobile;
        }
    };
    var siteId = location.search.match('site=([^&]*)')[1];
    if (/MicroMessenger/i.test(navigator.userAgent)) {
        $scope.snsClient = 'wx';
    } else if (/YiXin/i.test(navigator.userAgent)) {
        $scope.snsClient = 'yx';
    }
    $scope.posting = false;
    $scope.errmsg = '';
    $scope.loginUser = {};
    $scope.subView = 'register';
    $scope.switchSubView = function(name) {
        $scope.subView = name;
    };
    $scope.login = function() {
        $http.post('/rest/site/fe/user/login/do?site=' + siteId, $scope.loginUser).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.User = rsp.data;
            setMember($scope.User);
        }).error(function(text) {
            $scope.errmsg = text;
        });
    };
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                return value === $scope.password;
            }
        };
    })();
    $scope.register = function() {
        $http.post('/rest/site/fe/user/register/do?site=' + siteId, {
            uname: $scope.loginUser.uname,
            password: $scope.loginUser.password
        }).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.User = rsp.data;
            setMember($scope.User);
        }).error(function(text) {
            $scope.errmsg = text;
        });
    };
    $scope.doAuth = function() {
        var url;
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        sendRequest(LS.j('doAuth', 'site', 'schema'));
    };
    $scope.doReauth = function() {
        var url;
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        sendRequest(LS.j('doReauth', 'site', 'schema'));
    };
    $scope.$watchCollection('member', function() {
        $scope.errmsg = '';
    });
    $scope.$watch('callback', function(nv) {
        nv && nv.length && ($scope.callback = decodeURIComponent(nv));
    });
    $http.get(LS.j('pageGet', 'site', 'schema')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        $scope.User = rsp.data.user;
        setMember($scope.User);
        $scope.Page = rsp.data.schema.page;
        $scope.attrs = {};
        angular.forEach(rsp.data.attrs, function(attr, name) {
            $scope.attrs[name] = true;
        });
        $timeout(function() {
            $scope.$broadcast('xxt.member.auth.ready', rsp.data);
        });
    }).error(function(content, httpCode) {
        $scope.errmsg = content;
    });
}]);