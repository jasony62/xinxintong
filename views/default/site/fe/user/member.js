'use strict';
require('./member.css');
require('../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../asset/js/xxt.ui.http.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMember', ['$scope', '$timeout', 'noticebox', 'tmsLocation', 'http2', function($scope, $timeout, noticebox, LS, http2) {
    var validate = function() {
        var required = function(value, len, alerttext) {
            if (value == null || value == "" || value.length < len) {
                noticebox.warn(alerttext);
                return false;
            } else
                return true;
        };
        var isMobile = function(value, alerttext) {
            if (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) {
                noticebox.warn(alerttext);
                return false;
            } else {
                return true;
            }
        };
        var isEmail = function(value, alerttext) {
            if (value === undefined) {
                noticebox.warn(alerttext);
                return false;
            }
            var apos = value.indexOf("@"),
                dotpos = value.lastIndexOf(".");
            if (apos < 1 || dotpos - apos < 2) {
                noticebox.warn(alerttext);
                return false;
            } else {
                return true;
            }
        };
        var member = $scope.member;
        if (member.name !== undefined && false === required(member.name, 2, '请提供您的姓名！')) {
            return false;
        }
        if (member.mobile !== undefined && false === isMobile(member.mobile, '请提供正确的手机号（11位数字）！')) {
            return false;
        }
        if (member.email !== undefined && false === isEmail(member.email, '请提供正确的邮箱！')) {
            return false;
        }
        return true;
    };

    function sendRequest(url) {
        var oPosted;
        $scope.posting = true;
        http2.post(url, $scope.member, { autoBreak: false }).then(function(rsp) {
            $scope.posting = false;
            http2.get(LS.j('passed', 'site', 'schema') + '&redirect=N').then(function(rsp) {
                location.href = rsp.data;
            });
        }, function() {
            $scope.posting = false;
        });
    }

    function setMember(user) {
        var oMember, oAttrs;
        user.members && (oMember = user.members[LS.s().schema]);
        $scope.member = {
            schema_id: LS.s().schema
        };
        oAttrs = $scope.schema.attrs;
        if (oMember) {
            $scope.member.id = oMember.id;
            $scope.member.verified = oMember.verified;
            !oAttrs.name.hide && ($scope.member.name = oMember.name);
            !oAttrs.email.hide && ($scope.member.email = oMember.email);
            !oAttrs.mobile.hide && ($scope.member.mobile = oMember.mobile);
            oMember.extattr && ($scope.member.extattr = oMember.extattr);
        } else if (user.login) {
            //!oAttrs.name.hide && ($scope.member.name = user.login.nickname);
            if (!oAttrs.mobile.hide && /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(user.login.uname)) {
                $scope.member.mobile = user.login.uname
            }
            if (!oAttrs.email.hide && user.login.uname.indexOf("@") !== -1) {
                $scope.member.email = user.login.uname
            }
        }
    }

    $scope.posting = false;
    $scope.loginUser = {};
    $scope.subView = 'login';
    $scope.switchSubView = function(name) {
        $scope.subView = name;
    };
    $scope.login = function() {
        http2.post('/rest/site/fe/user/login/do?site=' + LS.s().site, $scope.loginUser).then(function(rsp) {
            http2.get(LS.j('get', 'site', 'schema')).then(function(rsp) {
                var user = rsp.data;
                $scope.user = user;
                setMember(user);
            });
        });
    };
    $scope.logout = function() {
        http2.post('/rest/site/fe/user/logout/do?site=' + LS.s().site, $scope.loginUser).then(function(rsp) {
            location.reload(true);
        });
    };
    $scope.register = function() {
        http2.post('/rest/site/fe/user/register/do?site=' + LS.s().site, {
            uname: $scope.loginUser.uname,
            nickname: $scope.loginUser.nickname,
            password: $scope.loginUser.password
        }).then(function(rsp) {
            $scope.user = rsp.data;
            setMember($scope.user);
        });
    };
    $scope.gotoHome = function() {
        location.href = '/rest/site/fe/user?site=' + LS.s().site;
    };
    $scope.repeatPwd = (function() {
        return {
            test: function(value) {
                return value === $scope.password;
            }
        };
    })();
    $scope.doAuth = function(ignoreCheck) {
        if (!ignoreCheck) {
            if (!validate()) {
                return;
            }
            if (document.querySelectorAll('.ng-invalid-required').length) {
                noticebox.warn('请填写必填项');
                return;
            }
        }
        sendRequest(LS.j('doAuth', 'site', 'schema'));
    };
    $scope.doReauth = function() {
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            noticebox.warn('请填写必填项');
            return;
        }
        sendRequest(LS.j('doReauth', 'site', 'schema'));
    };
    $scope.refreshPin = function(preEle) {
        var time, url, pinWidth;
        preEle ? preEle : preEle = document.getElementById('pinInput');
        if (preEle) {
            time = new Date * 1;
            //pinWidth = preEle.offsetWidth - 20;
            pinWidth = 120;
            url = '/rest/site/fe/user/login/getCaptcha?site=platform&codelen=4&width=' + pinWidth + '&height=32&fontsize=20';
            $scope.pinImg = url + '&' + time;
        }
    };
    http2.get('/rest/site/fe/get?site=' + LS.s().site).then(function(rsp) {
        $scope.site = rsp.data;
        http2.get('/rest/site/fe/user/memberschema/get?site=' + LS.s().site + '&schema=' + LS.s().schema + '&matter=' + LS.s().matter).then(function(rsp) {
            var oMschema;
            $scope.schema = oMschema = rsp.data.schema;
            $scope.matter = rsp.data.matter;
            http2.get(LS.j('get', 'site', 'schema')).then(function(rsp2) {
                $scope.user = rsp2.data;
                /*内置用户认证信息*/
                setMember($scope.user);
                /*社交账号信息*/
                if ($scope.user.sns) {
                    if ($scope.user.sns.wx) {
                        $scope.loginUser.nickname = $scope.user.sns.wx.nickname;
                    } else if ($scope.user.sns.yx) {
                        $scope.loginUser.nickname = $scope.user.sns.yx.nickname;
                    }
                }
                $timeout(function() {
                    var preEle = document.getElementById('pinInput');
                    if (preEle) {
                        $scope.refreshPin(preEle);
                    }
                });
            });
        });
    });
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width <= 768) {
        $scope.isSmallLayout = true;
    }
}]);