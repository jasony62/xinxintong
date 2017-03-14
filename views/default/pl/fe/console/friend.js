define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlFriend', ['$scope', 'srvSite', 'http2', '$uibModal', function($scope, srvSite, http2, $uibModal) {
        $scope.subscribe = function(site) {
            var url = '/rest/pl/fe/site/canSubscribe?site=' + site.siteid + '&_=' + (new Date() * 1);
            http2.get(url, function(rsp) {
                var sites = rsp.data;
                if (sites.length === 1) {

                } else if (sites.length === 0) {

                } else {
                    $uibModal.open({
                        templateUrl: 'subscribeSite.html',
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
                        var url = '/rest/pl/fe/site/subscribe?site=' + site.siteid;
                        sites = [];

                        selected.forEach(function(mySite) {
                            sites.push(mySite.id);
                        });
                        url += '&subscriber=' + sites.join(',');
                        http2.get(url, function(rsp) {});
                    });
                }
            });
        };
        $scope.unsubscribe = function(site, friend) {
            http2.get('/rest/pl/fe/site/unsubscribe?site=' + site.id + '&friend=' + friend.from_siteid, function(rsp) {
                $scope.friendSites.splice($scope.friendSites.indexOf(site), 1);
            });
        };
        $scope.openSite = function(siteId) {
            location.href = '/rest/site/home?site=' + siteId;
        };
        srvSite.matterList().then(function(result) {
            $scope.matters = result.matters;
        });
        srvSite.friendList().then(function(sites) {
            $scope.friendSites = sites;
        });
        srvSite.publicList().then(function(result) {
            $scope.publicSites = result.sites;
        });
    }]);
});
