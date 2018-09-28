'use strict';
require('../../../asset/js/xxt.ui.http.js');
require('../../../asset/js/xxt.ui.page.js');
require('../../../asset/js/xxt.ui.subscribe.js');
require('../../../asset/js/xxt.ui.contribute.js');
require('../../../asset/js/xxt.ui.favor.js');
require('../../../asset/js/xxt.ui.forward.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'page.ui.xxt', 'subscribe.ui.xxt', 'contribute.ui.xxt', 'favor.ui.xxt', 'forward.ui.xxt']);
ngApp.provider('srvUser', function() {
    var _getSiteUserDeferred;
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            getSiteUser: function(siteId) {
                if (_getSiteUserDeferred) {
                    return _getSiteUserDeferred.promise;
                }
                _getSiteUserDeferred = $q.defer();
                http2.get('/rest/site/fe/user/get?site=' + siteId).then(function(rsp) {
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
ngApp.directive('autoHeight', ['$window', function($window) {
    return {
        restrict: 'A',
        scope: {},
        link: function($scope, element, attrs) {
            var winowHeight = $window.innerHeight; //获取窗口高度
            var headerHeight = 52;
            var footerHeight = 50;
            element.css('min-height',
                (winowHeight - headerHeight - footerHeight) + 'px');
        }
    }
}]);
ngApp.directive('imageonload', function() {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            element.bind('load', function() {
                scope.$apply(attrs.imageonload);
            })
        }
    }
});
ngApp.controller('ctrlMain', ['$scope', '$q', '$uibModal', 'http2', 'srvUser', 'tmsDynaPage', 'tmsSubscribe', 'tmsContribute', 'tmsFavor', 'tmsForward', function($scope, $q, $uibModal, http2, srvUser, tmsDynaPage, tmsSubscribe, tmsContribute, tmsFavor, tmsForward) {
    function createSite() {
        var defer = $q.defer(),
            url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);

        http2.get(url).then(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function useTemplate(site, template) {
        var url = '/rest/pl/fe/template/purchase?template=' + template.id;
        url += '&site=' + site.id;

        http2.get(url).then(function(rsp) {
            http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + template.id).then(function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + site.id;
            });
        });
    }

    var oUser, ls = location.search,
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
    $scope.favorTemplate = function(template, asAdmin) {
        if (oUser.loginExpire) {
            var url = '/rest/pl/fe/template/siteCanFavor?template=' + template.id + '&_=' + (new Date() * 1);
            http2.get(url).then(function(rsp) {
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
                    http2.get(url).then(function(rsp) {});
                });
            });
        }
    };
    $scope.useTemplate = function(template, asAdmin) {
        if (oUser.loginExpire) {
            var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
            http2.get(url).then(function(rsp) {
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
    $scope.contributeSite = function() {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'contributeSite',
                });
                window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/access?site=platform#login';
        } else {
            tmsContribute.open(oUser, $scope.site);
        }
    };
    $scope.subscribeSite = function() {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                });
                window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/access?site=platform#login';
        } else {
            tmsSubscribe.open(oUser, $scope.site);
        }
    };
    http2.get('/rest/site/home/get?site=' + siteId).then(function(rsp) {
        srvUser.getSiteUser(siteId).then(function(siteUser) {
            $scope.user = oUser = siteUser;
            if (window.sessionStorage) {
                var pendingMethod;
                if (pendingMethod = window.sessionStorage.getItem('xxt.site.home.auth.pending')) {
                    window.sessionStorage.removeItem('xxt.site.home.auth.pending');
                    if (oUser.loginExpire) {
                        pendingMethod = JSON.parse(pendingMethod);
                        $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                    }
                }
            }
        });
        tmsDynaPage.loadCode(ngApp, rsp.data.home_page).then(function() {
            if (!rsp.data.heading_pic) {
                rsp.data.heading_pic = '/static/img/avatar.png';
            }
            $scope.site = rsp.data;
            $scope.page = rsp.data.home_page;
        });
    });
}]);

module.exports = ngApp;