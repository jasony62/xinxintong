define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$controllerProvider', function($lp, $cp) {
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.provider('srvUser', function() {
        var _getSiteAdminDeferred, _getSiteUserDeferred;
        this.$get = ['$q', 'http2', function($q, http2) {
            return {
                getSiteAdmin: function() {
                    if (_getSiteAdminDeferred) {
                        return _getSiteAdminDeferred.promise;
                    }
                    _getSiteAdminDeferred = $q.defer();
                    http2.get('/rest/pl/fe/user/get', function(rsp) {
                        _getSiteAdminDeferred.resolve(rsp.data);
                    });
                    return _getSiteAdminDeferred.promise;
                },
                getSiteUser: function(siteId) {
                    if (_getSiteUserDeferred) {
                        return _getSiteUserDeferred.promise;
                    }
                    _getSiteUserDeferred = $q.defer();
                    http2.get('/rest/site/fe/user/get?site=' + siteId, function(rsp) {
                        _getSiteUserDeferred.resolve(rsp.data);
                    });
                    return _getSiteUserDeferred.promise;
                }
            };
        }];
    });
    ngApp.controller('ctrlMain', ['$scope', '$q', 'http2', 'srvUser', function($scope, $q, http2, srvUser) {
        function getUser() {
            var defer = $q.defer(),
                userRole;

            if (window.localStorage) {
                userRole = window.localStorage.getItem('xxt.site.home.user.role');
            }
            if (userRole === 'person') {
                srvUser.getSiteUser('platform').then(function(siteUser) {
                    if (siteUser.loginExpire) {
                        user.nickname = siteUser.nickname;
                        user.role = 'person';
                        user.isLogin = true;
                        user.account = siteUser;
                        defer.resolve(user);
                    } else {
                        srvUser.getSiteAdmin().then(function(siteAdmin) {
                            if (siteAdmin) {
                                user.nickname = siteAdmin.nickname;
                                user.role = 'admin';
                                user.isLogin = true;
                                user.account = siteAdmin;
                            } else {
                                user.isLogin = false;
                            }
                        });
                        defer.resolve(user);
                    }
                });
            } else {
                srvUser.getSiteAdmin().then(function(siteAdmin) {
                    if (false === siteAdmin) {
                        srvUser.getSiteUser('platform').then(function(siteUser) {
                            if (siteUser.loginExpire) {
                                user.nickname = siteUser.nickname;
                                user.role = 'person';
                                user.isLogin = true;
                                user.account = siteUser;
                            } else {
                                user.isLogin = false;
                            }
                            defer.resolve(user);
                        });
                    } else {
                        user.nickname = siteAdmin.nickname;
                        user.role = 'admin';
                        user.isLogin = true;
                        user.account = siteAdmin;
                        defer.resolve(user);
                    }
                });
            }
            return defer.promise;
        }

        var platform, user, pages = {};
        $scope.user = user = {};
        $scope.subView = '';
        $scope.shiftPage = function(subView) {
            if ($scope.subView === subView) return;
            if (pages[subView] === undefined) {
                codeAssembler.loadCode(ngApp, platform[subView + '_page']).then(function() {
                    pages[subView] = platform[subView + '_page'];
                    $scope.page = pages[subView] || {
                        html: '<div></div>'
                    };
                    $scope.subView = subView;
                });
            } else {
                $scope.page = pages[subView] || {
                    html: '<div></div>'
                };
                $scope.subView = subView;
            }
        };
        $scope.openSite = function(site) {
            location.href = '/rest/site/home?site=' + site.siteid;
        };

        $scope.listApps = function() {
            http2.get('/rest/home/listApp', function(rsp) {
                $scope.apps = rsp.data.matters;
            });
        };
        $scope.listArticles = function() {
            http2.get('/rest/home/listArticle', function(rsp) {
                $scope.articles = rsp.data.matters;
            });
        };
        $scope.shiftAsAdmin = function() {
            srvUser.getSiteAdmin().then(function(siteAdmin) {
                if (window.localStorage) {
                    window.localStorage.setItem('xxt.site.home.user.role', 'admin');
                }
                if (siteAdmin) {
                    user.nickname = siteAdmin.nickname;
                    user.role = 'admin';
                    user.isLogin = true;
                    user.account = siteAdmin;
                } else {
                    location.href = '/rest/pl/fe/user/auth';
                }
            });
        };
        $scope.shiftAsPerson = function() {
            srvUser.getSiteUser('platform').then(function(siteUser) {
                if (window.localStorage) {
                    window.localStorage.setItem('xxt.site.home.user.role', 'person');
                }
                if (siteUser.loginExpire) {
                    user.nickname = siteUser.nickname;
                    user.role = 'person';
                    user.isLogin = true;
                    user.account = siteUser;
                } else {
                    location.href = '/rest/site/fe/user/login?site=platform';
                }
            });
        };
        http2.get('/rest/home/get', function(rsp) {
            platform = rsp.data.platform;
            if (platform.home_page === false) {
                location.href = '/rest/pl/fe';
            } else {
                $scope.platform = platform;
                $scope.shiftPage('home');
            }
            getUser().then(function(user) {
                if (window.sessionStorage) {
                    var pendingMethod;
                    if (pendingMethod = window.sessionStorage.getItem('xxt.home.auth.pending')) {
                        window.sessionStorage.removeItem('xxt.home.auth.pending');
                        if (user.isLogin) {
                            pendingMethod = JSON.parse(pendingMethod);
                            $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                        }
                    }
                }
            });
        });
    }]);
    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});
