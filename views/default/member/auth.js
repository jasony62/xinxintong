angular.module('xxtApp', []).
controller('authCtrl',['$scope','$http', function($scope,$http){
    if (/MicroMessenger/.test(navigator.userAgent)) {
        document.addEventListener('WeixinJSBridgeReady', function() {
            WeixinJSBridge.call('hideOptionMenu');
        }, false);
    } else if (/YiXin/.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function() {
            YixinJSBridge.call('hideOptionMenu');
        }, false);
    }
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
            var apos = value.indexOf("@"), dotpos = value.lastIndexOf(".");
            if (apos<1 || dotpos - apos < 2) {
                $scope.errmsg = alerttext;
                return false;
            } else {
                return true;
            }
        };
        var member = $scope.member;
        if (member.name !== undefined && false === required(member.name, 2, '请提供您的姓名！')) {
            $('[ng-model="member.name"]').focus();return false;
        }
        if (member.mobile !== undefined && false === isMobile(member.mobile, '请提供正确的手机号（11位数字）！')) {
            $('[ng-model="member.mobile"]').focus();return false;
        }
        if (member.email !== undefined && false === isEmail(member.email, '请提供正确的邮箱！')) {
            $('[ng-model="member.email"]').focus();return false;
        }
        if (member.password !== undefined && false === required(member.password, 6, '密码不能少于6位！')) {
            $('[ng-model="member.password"]').focus();return false;
        }
        if (member.password2 !== undefined && member.password !== member.password2) {
            $scope.errmsg = '两次输入的密码不一致，请再次输入';
            $('[ng-model="member.password2"]').focus();
            return false;
        }
        return true;
    };
    var sendAuthRequest = function(url) {
        $scope.posting = true;
        $http.post(url, $scope.member).
        success(function(rsp){
            $scope.posting = false;
            if (angular.isString(rsp)) {
                $scope.errmsg = rsp;
                return;
            }
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            location.href = $scope.callback+'&mid='+rsp.data;
        });
    };
    $scope.posting = false;
    $scope.errmsg = '';
    $scope.member = {};
    $scope.$watchCollection('member', function(){
        $scope.errmsg = '';
    });
    $scope.doAuth = function() {
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        var url = '/rest/member/auth/doAuth?mpid='+$scope.mpid+'&authid='+$scope.authid;
        sendAuthRequest(url);
    };
    $scope.doReauth = function() {
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        var url = '/rest/member/auth/doReauth?mpid='+$scope.mpid+'&authid='+$scope.authid;
        sendAuthRequest(url);
    };
    $scope.$watch('callback', function(nv){
        nv && nv.length && ($scope.callback = decodeURIComponent(nv));
    });
    $scope.$watch('jsonAuthedMember', function(nv){
        nv && nv.length && ($scope.authedMember = JSON.parse(decodeURIComponent(nv)));
    });
}]);
