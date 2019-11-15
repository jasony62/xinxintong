'use strict';
require('../matter/enroll/directive.css');
require('./member.css');
require('../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../asset/js/xxt.ui.http.js');
require('../../../../../asset/js/xxt.ui.image.js');
require('../../../../../asset/js/xxt.ui.schema.js');
require('../matter/enroll/directive.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt', 'directive.enroll', 'schema.ui.xxt']);
ngApp.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.directive('tmsImageInput', ['$compile', '$q', function ($compile, $q) {
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', 'noticebox', function ($scope, $timeout, noticebox) {
            $scope.chooseImage = function (oSchema) {
                var oExtattr;
                if ($scope.member.extattr) {
                    oExtattr = $scope.member.extattr;
                } else {
                    oExtattr = $scope.member.extattr = {};
                }
                if (oSchema !== null) {
                    aModifiedImgFields.indexOf(oSchema.id) === -1 && aModifiedImgFields.push(oSchema.id);
                    oExtattr[oSchema.id] === undefined && (oExtattr[oSchema.id] = []);
                    if (oExtattr[oSchema.id].length >= 1) {
                        noticebox.warn('最多允许上传（1）张图片');
                        return;
                    }
                }
                window.xxt.image.choose($q.defer()).then(function (imgs) {
                    var phase;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        oExtattr[oSchema.id] = oExtattr[oSchema.id].concat(imgs);
                    } else {
                        $scope.$apply(function () {
                            oExtattr[oSchema.id] = oExtattr[oSchema.id].concat(imgs);
                        });
                    }
                    $timeout(function () {
                        var i, j, img, eleImg;
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            eleImg = document.querySelector('ul[name="' + oSchema.id + '"] li:nth-last-child(2) img');
                            if (eleImg) {
                                eleImg.setAttribute('src', img.imgSrc);
                            }
                        }
                    });
                });
            };
            $scope.removeImage = function (oSchema, index) {
                $scope.member.extattr[oSchema.id].splice(index, 1);
            };
        }]
    }
}]);
ngApp.controller('ctrlMember', ['$scope', '$timeout', 'noticebox', 'tmsLocation', 'http2', 'tmsSchema', function ($scope, $timeout, noticebox, LS, http2, tmsSchema) {
    function fnValidate(oSchema) {
        function required(value, len, alerttext) {
            if (value == null || value == "" || value.length < len) {
                noticebox.warn(alerttext);
                return false;
            } else
                return true;
        }

        function isMobile(value, alerttext) {
            if (false === /^1[3|4|5|7|8|9][0-9]\d{4,8}$/.test(value)) {
                noticebox.warn(alerttext);
                return false;
            } else {
                return true;
            }
        }

        function isEmail(value, alerttext) {
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
        }

        var member = $scope.member;
        if (member.name && false === required(member.name, 2, '请提供您的姓名！')) {
            return false;
        }
        if (member.mobile && false === isMobile(member.mobile, '请提供正确的手机号（11位数字）！')) {
            return false;
        }
        if (member.email && false === isEmail(member.email, '请提供正确的邮箱！')) {
            return false;
        }
        if (oSchema.extAttrs && oSchema.extAttrs.length) {
            var oExtValue, oExtAttr, sCheckResult;
            oExtValue = member.extattr || {};
            for (var i = 0, ii = oSchema.extAttrs.length; i < ii; i++) {
                oExtAttr = oSchema.extAttrs[i];
                if (oExtAttr.required && (oExtAttr.required === 'Y')) {
                    sCheckResult = tmsSchema.checkValue(oExtAttr, oExtValue[oExtAttr.id]);
                    if (true !== sCheckResult) {
                        noticebox.warn(sCheckResult);
                        return false;
                    }
                }
            }
        }
        return true;
    };

    function sendRequest(url) {
        $scope.posting = true;
        http2.post(url, $scope.member, {
            autoBreak: false
        }).then(function (rsp) {
            var actions = [{
                label: '取消',
                value: 'cancel'
            }, {
                label: '离开',
                value: 'continue',
                execWait: 5000
            }];
            $scope.posting = false;
            noticebox.confirm('已经提交成功，离开页面！', actions).then(function (action) {
                if (action === 'continue') {
                    http2.get(LS.j('passed', 'site', 'schema') + '&redirect=N').then(function (rsp) {
                        if (window.parent && window.parent.onClosePlugin) {
                            window.parent.onClosePlugin(rsp.data);
                        } else {
                            location.href = rsp.data;
                        }
                    });
                }
            });
        }, function () {
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
    $scope.isRegister = false;
    $scope.switchSubView = function (name) {
        $scope.subView = name;
    };
    $scope.openThirdAppUrl = function (thirdApp) {
        location.href = '/rest/site/fe/user/login/byRegAndThird?thirdId=' + thirdApp.id;
    };
    $scope.login = function () {
        if ($scope.loginUser.password) {
            http2.post('/rest/site/fe/user/login/do?site=' + LS.s().site, $scope.loginUser).then(function (rsp) {
                http2.post('/rest/site/fe/user/login/checkPwdStrength', {
                    'account': $scope.loginUser.uname,
                    'password': $scope.loginUser.password
                }).then(function (rsp) {
                    if (!rsp.data.strength) {
                        alert(rsp.data.msg);
                    }
                    http2.get(LS.j('get', 'site', 'schema')).then(function (rsp) {
                        var user = rsp.data;
                        $scope.user = user;
                        setMember(user);
                    });
                });
            });
        }
    };
    $scope.loginByReg = function (oRegUser) {
        http2.post('/rest/site/fe/user/login/byRegAndWxopenid?site=' + LS.s().site, oRegUser).then(function (rsp) {
            location.reload(true);
        });
    };
    $scope.logout = function () {
        http2.post('/rest/site/fe/user/logout/do?site=' + LS.s().site, $scope.loginUser).then(function (rsp) {
            location.reload(true);
        });
    };
    $scope.register = function () {
        http2.post('/rest/site/fe/user/register/do?site=' + LS.s().site, {
            uname: $scope.loginUser.uname,
            nickname: $scope.loginUser.nickname,
            password: $scope.loginUser.password,
            pin: $scope.loginUser.pin
        }).then(function (rsp) {
            $scope.user = rsp.data;
            setMember($scope.user);
        });
    };
    $scope.gotoHome = function () {
        location.href = '/rest/site/fe/user?site=' + LS.s().site;
    };
    $scope.repeatPwd = (function () {
        return {
            test: function (value) {
                return value === $scope.password;
            }
        };
    })();
    $scope.doAuth = function (ignoreCheck) {
        if (!ignoreCheck) {
            if (!fnValidate($scope.schema)) {
                return;
            }
            if (document.querySelectorAll('.ng-invalid-required').length) {
                noticebox.warn('请填写必填项');
                return;
            }
        }
        sendRequest(LS.j('doAuth', 'site', 'schema'));
    };
    $scope.doReauth = function () {
        if (!fnValidate($scope.schema)) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            noticebox.warn('请填写必填项');
            return;
        }
        sendRequest(LS.j('doReauth', 'site', 'schema'));
    };
    $scope.refreshPin = function (preEle) {
        var time, url, pinWidth;
        preEle ? preEle : preEle = document.getElementById('pinInput');
        if (preEle) {
            time = new Date * 1;
            //pinWidth = preEle.offsetWidth - 20;
            pinWidth = 120;
            url = '/rest/site/fe/user/login/getCaptcha?site=platform&codelen=4&width=' + pinWidth + '&height=32&fontsize=20';
            $scope.pinImg = url + '&_=' + time;
        }
    };
    $scope.shiftRegUser = function (oOtherRegUser) {
        http2.post('/rest/site/fe/user/shiftRegUser?site=' + LS.s().site, {
            uname: oOtherRegUser.uname
        }).then(function (rsp) {
            location.reload(true);
        });
    };
    http2.get('/rest/site/fe/get?site=' + LS.s().site).then(function (rsp) {
        $scope.site = rsp.data;
        http2.get('/rest/site/fe/user/memberschema/get?site=' + LS.s().site + '&schema=' + LS.s().schema + '&matter=' + LS.s().matter).then(function (rsp) {
            var oMschema;
            $scope.schema = oMschema = rsp.data.schema;
            $scope.matter = rsp.data.matter;
            http2.get(LS.j('get', 'site', 'schema')).then(function (rsp2) {
                $scope.user = rsp2.data;
                /*内置用户认证信息*/
                setMember($scope.user);
                /*社交账号信息*/
                if ($scope.user.sns) {
                    if ($scope.user.sns.wx) {
                        $scope.loginUser.nickname = $scope.user.sns.wx.nickname;
                    }
                }
                $timeout(function () {
                    var preEle = document.getElementById('pinInput');
                    if (preEle) {
                        $scope.refreshPin(preEle);
                    }
                });
                /* 解决多个注册账号的问题 */
                http2.get('/rest/site/fe/user/get?site=' + LS.s().site).then(function (rsp) {
                    if (rsp.data.siteRegistersByWx) {
                        $scope.user.siteRegistersByWx = rsp.data.siteRegistersByWx;
                    }
                    if (rsp.data.plRegistersByWx) {
                        $scope.user.plRegistersByWx = rsp.data.plRegistersByWx;
                    }
                });
            });
        });
    });
    http2.get('/rest/site/fe/user/login/thirdList').then(function (rsp) {
        $scope.thirdApps = rsp.data;
    });
    http2.get('/rest/site/fe/user/getSafetyLevel').then(function (rsp) {
        $scope.isRegister = rsp.data.register;
    });
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width <= 768) {
        $scope.isSmallLayout = true;
    }
}]);