define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', ['http.ui.xxt']);
    ngApp.service('userService', ['http2', '$q', function(http2, $q) {
        var _baseUrl = '/rest/site/fe/user',
            _user;
        return {
            get: function() {
                var deferred = $q.defer();
                http2.get(_baseUrl + '/get?site=' + siteId).then(function(rsp) {
                    _user = rsp.data;
                    if (!_user.headimgurl) {
                        _user.headimgurl = '/static/img/avatar.png';
                    }
                    if (!_user.uname) {
                        _user.uname = '未知昵称';
                    }
                    deferred.resolve(_user);
                });
                return deferred.promise;
            },
            changePwd: function(data) {
                var deferred = $q.defer();
                http2.post(_baseUrl + '/changePwd?site=' + siteId, data).then(function(rsp) {
                    _user = rsp.data;
                    deferred.resolve(_user);
                });
                return deferred.promise;
            },
            changeNickname: function(data) {
                var deferred = $q.defer();
                http2.post(_baseUrl + '/changeNickname?site=' + siteId, data).then(function(rsp) {
                    _user = rsp.data;
                    deferred.resolve(_user);
                });
                return deferred.promise;
            }
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$timeout', 'http2', 'userService', function($scope, $timeout, http2, userService) {
        function newSubscriptions(afterAt) {
            var url;
            url = '/rest/site/fe/user/subscribe/count?site=' + siteId + '&after=' + afterAt;
            http2.get(url).then(function(rsp) {
                $scope.count.newSubscriptions = rsp.data;
            });
        }

        var cachedStatus, lastCachedStatus;
        $scope.count = {};
        $scope.userSetting = false;
        $scope.toggleUserSetting = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.target.classList.contains('list-group-item')) {
                $scope.userSetting = !$scope.userSetting;
            }
        };
        $scope.changeNickname = function() {
            var data = {};
            data.nickname = $scope.user.nickname;
            userService.changeNickname(data).then(function() {
                alert('修改成功');
            });
        };
        $scope.changePwd = function() {
            var data = {};
            data.password = $scope.user.password;
            userService.changePwd(data).then(function() {
                alert('修改成功');
            });
        };
        $scope.logout = function() {
            http2.get('/rest/site/fe/user/logout/do?site=' + siteId).then(function(rsp) {
                location.replace('/rest/site/fe/user?site=' + siteId);
            });
        };
        $scope.gotoRegister = function() {
            location.href = '/rest/site/fe/user/access?site=' + siteId + '#register';
        };
        $scope.gotoLogin = function() {
            location.href = '/rest/site/fe/user/access?site=' + siteId + '#login';
        };
        $scope.gotoMember = function(memberSchema) {
            location.href = '/rest/site/fe/user/member?site=' + siteId + '&schema=' + memberSchema.id;
        };
        $scope.gotoConsole = function() {
            location.href = '/rest/pl/fe';
        };
        http2.get('/rest/site/fe/get?site=' + siteId).then(function(rsp) {
            $scope.site = rsp.data;
            userService.get().then(function(oUser) {
                $scope.user = oUser;
                if (oUser.unionid) {
                    http2.get('/rest/site/fe/user/memberschema/atHome?site=' + siteId).then(function(rsp) {
                        $scope.memberSchemas = rsp.data;
                    });
                    http2.get('/rest/site/fe/user/subscribe/count?site=' + siteId).then(function(rsp) {
                        $scope.count.subscription = rsp.data;
                    });
                    http2.get('/rest/site/fe/user/favor/count?site=' + siteId).then(function(rsp) {
                        $scope.count.favor = rsp.data;
                    });
                    http2.get('/rest/site/fe/user/notice/count?site=' + siteId).then(function(rsp) {
                        $scope.count.notice = rsp.data;
                    });
                    /*上一次访问状态*/
                    if (window.localStorage) {
                        if (cachedStatus = window.localStorage.getItem("site.fe.user.main")) {
                            cachedStatus = JSON.parse(cachedStatus);
                            lastCachedStatus = angular.copy(cachedStatus);
                        } else {
                            cachedStatus = {};
                        }
                        $timeout(function() {
                            cachedStatus.lastAt = parseInt((new Date() * 1) / 1000);
                            window.localStorage.setItem("site.fe.user.main", JSON.stringify(cachedStatus));
                        }, 6000);
                        if (lastCachedStatus && lastCachedStatus.lastAt) {
                            newSubscriptions(lastCachedStatus.lastAt);
                        }
                    }
                }
                window.loading.finish();
            });
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});