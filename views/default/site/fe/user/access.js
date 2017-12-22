'use strict';
var ngApp = angular.module('app', ['ui.bootstrap']);
ngApp.controller('ctrlAccess', ['$scope', '$http', function($scope, $http) {
    var _siteId;
    if (location.search.match('site=(.*)')) {
        _siteId = location.search.match('site=(.*)')[1];
    } else {
        _siteId = 'platform';
    }
    $scope.activeView = location.hash ? location.hash.substr(1) : 'login';
    $scope.loginData = {};
    $scope.registerData = {};
    if (window.localStorage) {
        $scope.supportLocalStorage = 'Y';
        if (window.localStorage.getItem('xxt.login.rememberMe') === 'Y') {
            $scope.loginData.uname = window.localStorage.getItem('xxt.login.email');
            $scope.loginData.rememberMe = 'Y';
            //document.querySelector('[ng-model="data.password"]').focus();
        } else {
            //document.querySelector('[ng-model="data.uname"]').focus();
        }
        if (window.localStorage.getItem('xxt.login.gotoConsole') === 'Y') {
            $scope.loginData.gotoConsole = window.localStorage.getItem('xxt.login.gotoConsole');
        }
    } else {
        $scope.supportLocalStorage = 'N';
        document.querySelector('[ng-model="data.uname"]').focus();
    }
    $scope.login = function() {
        $http.post('/rest/site/fe/user/login/do?site=' + _siteId, $scope.loginData).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            if ($scope.loginData.rememberMe === 'Y') {
                window.localStorage.setItem('xxt.login.rememberMe', 'Y');
                window.localStorage.setItem('xxt.login.email', $scope.loginData.uname);
            } else {
                window.localStorage.setItem('xxt.login.rememberMe', 'N');
                window.localStorage.removeItem('xxt.login.email');
            }
            if ($scope.loginData.gotoConsole === 'Y') {
                window.localStorage.setItem('xxt.login.gotoConsole', 'Y');
            } else {
                window.localStorage.setItem('xxt.login.gotoConsole', 'N');
            }
            if (window.parent && window.parent.onClosePlugin) {
                window.parent.onClosePlugin(rsp.data);
            } else if (rsp.data._loginReferer) {
                location.replace(rsp.data._loginReferer);
            } else if ($scope.loginData.gotoConsole === 'Y') {
                location.href = '/rest/pl/fe';
            } else {
                location.replace('/rest/site/fe/user?site=' + _siteId);
            }
        }).error(function(data, header, config, status) {
            if (data) {
                $http.post('/rest/log/add', { src: 'site.fe.user.login', msg: JSON.stringify(arguments) });
            }
            alert('操作失败：' + (data === null ? '网络不可用' : data));
        });
    };
    $scope.logout = function() {
        $http.get('/rest/site/fe/user/logout/do?site=' + _siteId).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            if (window.parent && window.parent.onClosePlugin) {
                window.parent.onClosePlugin(rsp.data);
            } else {
                location.replace('/rest/site/fe/user/login?site=' + site);
            }
        }).error(function(data, header, config, status) {
            $http.post('/rest/log/add', { src: 'site.fe.user.logout', msg: JSON.stringify(arguments) });
            alert('操作失败：' + (data === null ? '网络不可用' : data));
        });
    };
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                return value === $scope.registerData.password;
            }
        };
    })();
    $scope.register = function() {
        $http.post('/rest/site/fe/user/register/do?site=' + _siteId, {
            uname: $scope.registerData.uname,
            nickname: $scope.registerData.nickname,
            password: $scope.registerData.password
        }).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            if (rsp.data._loginReferer) {
                location.replace(rsp.data._loginReferer);
            } else {
                location.href = '/rest/site/fe/user?site=' + _siteId;
            }
        });
    };
    $scope.gotoSite = function() {
        if ($scope.site.id === 'platform') {
            location.href = '/rest/home';
        } else {
            location.href = '/rest/site/home?site=' + $scope.site.id;
        }
    };
    $scope.gotoHome = function() {
        location.href = '/rest/site/fe/user?site=' + _siteId;
    };
    $scope.refreshPin = function() {
        var url = '/rest/site/fe/user/login/getCaptcha?site=platform';
            url += '&codelen=4&width=130&height=34&fontsize=20';
        $http.get(url).success(function(rsp) {
            $scope.pinImg = rsp.data;
        });
    };
    $http.get('/rest/site/fe/get?site=' + _siteId).success(function(rsp) {
        $scope.site = rsp.data;
        $http.get('/rest/site/fe/user/get?site=' + _siteId).success(function(rsp) {
            var eleLoading, eleStyle;
            eleLoading = document.querySelector('.loading');
            eleLoading.parentNode.removeChild(eleLoading);
            $scope.user = rsp.data;
            $scope.refreshPin();
        }).error(function(data, header, config, status) {
            $http.post('/rest/log/add', { src: 'site.fe.user.access', msg: JSON.stringify(arguments) });
            alert('操作失败：' + (data === null ? '网络不可用' : data));
        });
    });
}]);