'use strict';
require('!style-loader!css-loader!./home.css');
require('../../asset/js/xxt.ui.page.js');

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'ui.tms', 'page.ui.xxt']);
ngApp.config(['$locationProvider', '$controllerProvider', '$uibTooltipProvider', function($lp, $cp, $uibTooltipProvider) {
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
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
ngApp.controller('ctrlMain', ['$scope', '$timeout', '$q', '$uibModal', 'http2', 'srvUser', 'tmsDynaPage', function($scope, $timeout, $q, $uibModal, http2, srvUser, tmsDynaPage) {
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

    var platform, user, pages = {},
        popoverUseTempateAsAdmin = false,
        popoverFavorTempateAsAdmin = false;

    $scope.user = user = {};
    $scope.subView = '';
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
    $scope.subscribeByPerson = function(site) {
        if (user.isLogin === false) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeByPerson',
                    args: [site]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            $scope.shiftAsPerson();
        } else {
            var url = '/rest/site/fe/user/site/subscribe?site=platform&target=' + site.siteid;
            http2.get(url, function(rsp) {
                site._subscribed = 'Y';
            });
        }
    };
    $scope.unsubscribeByPerson = function(site) {
        var url = '/rest/site/fe/user/site/unsubscribe?site=platform&target=' + site.siteid;
        http2.get(url, function(rsp) {
            site._subscribed = 'N';
        });
    };
    $scope.subscribeByTeam = function(site) {
        if (user.isLogin === false) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeByTeam',
                    args: [site]
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            $scope.shiftAsAdmin();
        } else {
            var url = '/rest/pl/fe/site/canSubscribe?site=' + site.siteid + '&_=' + (new Date() * 1);
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
                    var url = '/rest/pl/fe/site/subscribe?site=' + site.id;
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
    $scope.shiftPage = function(subView) {
        if ($scope.subView === subView) return;
        if (pages[subView] === undefined) {
            tmsDynaPage.loadCode(ngApp, platform[subView + '_page']).then(function() {
                pages[subView] = platform[subView + '_page'];
                $scope.page = pages[subView] || {
                    html: '<div></div>'
                };
                $scope.subView = subView;
                history.replaceState({}, '', '/rest/home/' + subView);
            });
        } else {
            $scope.page = pages[subView] || {
                html: '<div></div>'
            };
            $scope.subView = subView;
            history.replaceState({}, '', '/rest/home/' + subView);
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
                    window.sessionStorage.setItem('xxt.home.auth.pending', JSON.stringify(oPendingMethod));
                }
                location.href = '/rest/pl/fe/user/auth';
            }
        });
        return defer.promise;
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
    $scope.openSite = function(site) {
        location.href = '/rest/site/home?site=' + site.siteid;
    };
    $scope.openTemplate = function(template) {
        location.href = '/rest/site/fe/matter/template?template=' + template.id;
    };
    http2.get('/rest/home/get', function(rsp) {
        platform = rsp.data.platform;
        if (platform.home_page === false) {
            location.href = '/rest/pl/fe';
        } else {
            $scope.platform = platform;
            if (/\/site/.test(location.href)) {
                $scope.shiftPage('site');
            } else if (/\/template/.test(location.href)) {
                $scope.shiftPage('template');
            } else {
                $scope.shiftPage('home');
            }
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
module.exports = ngApp;

