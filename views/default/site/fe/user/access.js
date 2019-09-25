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
    $scope.isRegister = false;
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
    $scope.toggleVisible = function(event) {
        var target = event.target;
        if (target.tagName === 'SPAN' || ((target = target.parentNode) && target.tagName === 'SPAN')) {
            var childEle = target.querySelector("i");
            if (childEle.getAttribute("class") === "glyphicon glyphicon-eye-close") {
                childEle.setAttribute("class", "glyphicon glyphicon-eye-open");
                target.previousElementSibling.setAttribute("type", "text");
            } else {
                childEle.setAttribute("class", "glyphicon glyphicon-eye-close");
                target.previousElementSibling.setAttribute("type", "password");
            }
        }
    }
    if (window.sessionStorage) {
        var oSessionCached;
        if (window.sessionStorage.getItem('xxt.pl.protect.system')) {
            oSessionCached = window.sessionStorage.getItem('xxt.pl.protect.system');
            oSessionCached = JSON.parse(oSessionCached);
        } else {
            $http.get("/tmsappconfig.php").then(function(rsp) {
                oSessionCached = rsp.data;
                window.sessionStorage.setItem('xxt.pl.protect.system', JSON.stringify(oSessionCached));
            });
        }
    }
    $scope.login = function() {
        if ($scope.loginData.password) {
            $http.post('/rest/site/fe/user/login/do?site=' + _siteId, $scope.loginData).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    $scope.refreshPin();
                    return;
                }

                $http.post('/rest/site/fe/user/login/checkPwdStrength', { 'account': $scope.loginData.uname, 'password': $scope.loginData.password }).success(function(rsp2) {
                    if (!rsp2.data.strength) {
                        alert(rsp2.data.msg);
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
                    if (oSessionCached.noHookMaxTime && oSessionCached.noHookMaxTime > 0) {
                        var oStorage, oCached, intervaltime;
                        if (oStorage = window.localStorage) {
                            oCached = {};
                            oCached.lasttime = new Date() * 1;
                            oStorage.setItem('xxt.pl.protect.event.trace', JSON.stringify(oCached));
                        }
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
                });
            }).error(function(data, header, config, status) {
                if (data) {
                    $http.post('/rest/log/add', { src: 'site.fe.user.login', msg: JSON.stringify(arguments) });
                }
                alert('操作失败：' + (data === null ? '网络不可用' : data));
            });
        }
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
                location.replace('/rest/site/fe/user/login?site=' + _siteId);
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
            password: $scope.registerData.password,
            pin: $scope.registerData.pin
        }).success(function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            if (window.parent && window.parent.onClosePlugin) {
                window.parent.onClosePlugin(rsp.data);
            } else if (rsp.data._loginReferer) {
                location.replace(rsp.data._loginReferer);
            } else {
                location.href = '/rest/site/fe/user?site=' + _siteId;
            }
        });
    };
    $scope.openThirdAppUrl = function(thirdApp) {
        location.href = '/rest/site/fe/user/login/byRegAndThird?thirdId=' + thirdApp.id;
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
        var time = new Date().getTime(),
            url = '/rest/site/fe/user/login/getCaptcha?site=platform&codelen=4&width=130&height=34&fontsize=20';
        $scope.pinImg = url + '&_=' + time;
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
    $http.get('/rest/site/fe/user/login/thirdList').success(function(rsp) {
        $scope.thirdApps = rsp.data;
    });
    $http.get('/rest/site/fe/user/getSafetyLevel').success(function(rsp) {
        $scope.isRegister = rsp.data.register;
    });
}]);