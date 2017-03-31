define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms', 'discuss.ui.xxt']);
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
    ngApp.config(['$controllerProvider', '$uibTooltipProvider', function($cp, $uibTooltipProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$timeout', '$q', '$uibModal', 'http2', 'srvUser', function($scope, $timeout, $q, $uibModal, http2, srvUser) {
        function createSite() {
            var defer = $q.defer(),
                url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);

            http2.get(url, function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }

        function useTemplate(site, template) {
            var url = '/rest/pl/fe/template/purchase?template=' + template.id;
            url += '&site=' + site.id;

            http2.get(url, function(rsp) {
                http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + template.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + site.id;
                });
            });
        }

        function getUser() {
            var defer = $q.defer(),
                userRole;

            if (window.localStorage) {
                userRole = window.localStorage.getItem('xxt.site.home.user.role');
            }
            if (userRole === 'person') {
                srvUser.getSiteUser(siteId).then(function(siteUser) {
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
                        srvUser.getSiteUser(siteId).then(function(siteUser) {
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

        var user, ls = location.search,
            siteId = ls.match(/site=([^&]*)/)[1],
            popoverUseTempateAsAdmin = false,
            popoverFavorTempateAsAdmin = false;

        $('body').click(function() {
            if (popoverUseTempateAsAdmin) {
                $('#popoverUseTempateAsAdmin').trigger('hide');
                popoverUseTempateAsAdmin = false;
            }
            if (popoverFavorTempateAsAdmin) {
                $('#popoverFavorTempateAsAdmin').trigger('hide');
                popoverFavorTempateAsAdmin = false;
            }
        });
        $scope.user = user = {};
        $scope.favorTemplate = function(template, asAdmin) {
            if (user.role !== 'admin') {
                if (asAdmin) {
                    $scope.shiftAsAdmin({
                        name: 'favorTemplate',
                        args: [template]
                    }).then(function(user) {
                        $scope.favorTemplate(template);
                    });
                } else {
                    $('#popoverFavorTempateAsAdmin').trigger('show');
                    $timeout(function() {
                        popoverFavorTempateAsAdmin = true;
                    });
                    return;
                }
            }
            if (user.isLogin && user.role === 'admin') {
                var url = '/rest/pl/fe/template/siteCanFavor?template=' + template.id + '&_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
                    $uibModal.open({
                        templateUrl: 'favorTemplateSite.html',
                        dropback: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            $scope2.mySites = sites;
                            $scope2.ok = function() {
                                var selected = [];
                                sites.forEach(function(site) {
                                    site._selected === 'Y' && selected.push(site);
                                });
                                if (selected.length) {
                                    $mi.close(selected);
                                } else {
                                    $mi.dismiss();
                                }
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                        }]
                    }).result.then(function(selected) {
                        var url = '/rest/pl/fe/template/favor?template=' + template.id,
                            sites = [];

                        selected.forEach(function(site) {
                            sites.push(site.id);
                        });
                        url += '&site=' + sites.join(',');
                        http2.get(url, function(rsp) {});
                    });
                });
            }
        };
        $scope.useTemplate = function(template, asAdmin) {
            if (user.role !== 'admin') {
                if (asAdmin) {
                    $scope.shiftAsAdmin({
                        name: 'useTemplate',
                        args: [template]
                    }).then(function(user) {
                        $scope.useTemplate(template);
                    });
                } else {
                    $('#popoverUseTempateAsAdmin').trigger('show');
                    $timeout(function() {
                        popoverUseTempateAsAdmin = true;
                    });
                    return;
                }
            }

            if (user.isLogin && user.role === 'admin') {
                var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
                    if (sites.length === 1) {
                        useTemplate(sites[0], template);
                    } else if (sites.length === 0) {
                        createSite().then(function(site) {
                            useTemplate(site, template);
                        });
                    } else {
                        $uibModal.open({
                            templateUrl: 'useTemplateSite.html',
                            dropback: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                                var data;
                                $scope2.mySites = sites;
                                $scope2.data = data = {};
                                $scope2.ok = function() {
                                    if (data.index !== undefined) {
                                        $mi.close(sites[data.index]);
                                    } else {
                                        $mi.dismiss();
                                    }
                                };
                                $scope2.cancel = function() {
                                    $mi.dismiss();
                                };
                            }]
                        }).result.then(function(site) {
                            useTemplate(site, template);
                        });
                    }
                });
            }
        };
        $scope.subscribeByPerson = function() {
            if (user.isLogin === false) {
                if (window.sessionStorage) {
                    var method = JSON.stringify({
                        name: 'subscribeByPerson'
                    });
                    window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
                }
                location.href = '/rest/site/fe/user/login?site=' + siteId;
            } else {
                var url = '/rest/site/fe/user/site/subscribe?site=' + siteId + '&target=' + siteId;
                http2.get(url, function(rsp) {
                    $scope.site._subscribed = 'Y';
                });
            }
        };
        $scope.unsubscribeByPerson = function() {
            var url = '/rest/site/fe/user/site/unsubscribe?site=' + siteId + '&target=' + siteId;
            http2.get(url, function(rsp) {
                $scope.site._subscribed = 'N';
            });
        };
        $scope.subscribeByTeam = function() {
            if (user.isLogin === false) {
                if (window.sessionStorage) {
                    var method = JSON.stringify({
                        name: 'subscribeByTeam'
                    });
                    window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
                }
                location.href = '/rest/pl/fe/user/auth';
            } else {
                var url = '/rest/pl/fe/site/canSubscribe?site=' + siteId + '&_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
                    $uibModal.open({
                        templateUrl: 'subscribeByTeam.html',
                        dropback: 'static',
                        controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                            $scope2.mySites = sites;
                            $scope2.ok = function() {
                                var selected = [];
                                sites.forEach(function(site) {
                                    site._selected === 'Y' && selected.push(site);
                                });
                                if (selected.length) {
                                    $mi.close(selected);
                                } else {
                                    $mi.dismiss();
                                }
                            };
                            $scope2.cancel = function() {
                                $mi.dismiss();
                            };
                        }]
                    }).result.then(function(selected) {
                        var url = '/rest/pl/fe/site/subscribe?site=' + siteId;
                        sites = [];

                        selected.forEach(function(mySite) {
                            sites.push(mySite.id);
                        });
                        url += '&subscriber=' + sites.join(',');
                        http2.get(url, function(rsp) {});
                    });
                });
            }
        };
        $scope.shiftAsAdmin = function(oPendingMethod) {
            var defer = $q.defer();
            srvUser.getSiteAdmin().then(function(siteAdmin) {
                if (window.localStorage) {
                    window.localStorage.setItem('xxt.site.home.user.role', 'admin');
                }
                if (siteAdmin) {
                    user.nickname = siteAdmin.nickname;
                    user.role = 'admin';
                    user.isLogin = true;
                    user.account = siteAdmin;
                    defer.resolve(user);
                } else {
                    if (oPendingMethod && window.sessionStorage) {
                        window.sessionStorage.setItem('xxt.site.home.auth.pending', JSON.stringify(oPendingMethod));
                    }
                    location.href = '/rest/pl/fe/user/auth';
                }
            });
            return defer.promise;
        };
        $scope.shiftAsPerson = function() {
            srvUser.getSiteUser(siteId).then(function(siteUser) {
                if (window.localStorage) {
                    window.localStorage.setItem('xxt.site.home.user.role', 'person');
                }
                if (siteUser.loginExpire) {
                    user.nickname = siteUser.nickname;
                    user.role = 'person';
                    user.isLogin = true;
                    user.account = siteUser;
                } else {
                    location.href = '/rest/site/fe/user/login?site=' + siteId;
                }
            });
        };
        http2.get('/rest/site/home/get?site=' + siteId, function(rsp) {
            getUser().then(function(user) {
                if (window.sessionStorage) {
                    var pendingMethod;
                    if (pendingMethod = window.sessionStorage.getItem('xxt.site.home.auth.pending')) {
                        window.sessionStorage.removeItem('xxt.site.home.auth.pending');
                        if (user.isLogin) {
                            pendingMethod = JSON.parse(pendingMethod);
                            $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                        }
                    }
                }
            });
            codeAssembler.loadCode(ngApp, rsp.data.home_page).then(function() {
                $scope.site = rsp.data;
                $scope.page = rsp.data.home_page;
            });
        });
    }]);

    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});
