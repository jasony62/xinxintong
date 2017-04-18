define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlFriend', ['$scope', 'srvSite', 'http2', '$uibModal', 'noticebox', function($scope, srvSite, http2, $uibModal, noticebox) {
        $scope.subscribe = function(site) {
            var url = '/rest/pl/fe/site/canSubscribe?site=' + site.siteid + '&_=' + (new Date() * 1);
            http2.get(url, function(rsp) {
                function _chooseSite(chooseSite) {
                    var url = '/rest/pl/fe/site/subscribe?site=' + site.siteid;
                        sites = [];

                    chooseSite.forEach(function(mySite) {
                        sites.push(mySite.id);
                    });
                    url += '&subscriber=' + sites.join(',');
                    http2.get(url, function(rsp) {
                        site._subscribed = 'Y';
                    });
                }

                var sites = rsp.data;
                if (sites.length === 1) {
                    _chooseSite(sites);
                } else if (sites.length === 0) {
                    noticebox.error('请先创建用于关注团队的团队');
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
                        _chooseSite(selected);
                    });
                }
            });
        };
        $scope.unsubscribe = function(site, friend) {
            http2.get('/rest/pl/fe/site/unsubscribe?site=' + site.id + '&friend=' + friend.from_siteid, function(rsp) {
                $scope.friendSites.splice($scope.friendSites.indexOf(site), 1);
            });
        };
        $scope.openMatter = function(matter) {
            var url;
            if (/article|custom|news|channel/.test(matter.matter_type)) {
                url = '/rest/site/fe/matter?type=' + matter.matter_type;
                url += '&id=' + matter.matter_id + '&site=' + matter.from_siteid;
            } else {
                url = '/rest/site/fe/matter/' + matter.matter_type;
                url += '?id=' + matter.matter_id + '&site=' + matter.from_siteid;
            }
            location.href = url;
        };
        $scope.moreMatter = function() {
            srvSite.matterList($scope.pageOfmatters).then(function(result) {
                result.matters.forEach(function(matter) {
                    $scope.matters.push(matter);
                });
            });
        };
        srvSite.matterList().then(function(result) {
            $scope.matters = result.matters;
            $scope.pageOfmatters = result.page;
        });
        srvSite.friendList().then(function(sites) {
            $scope.friendSites = sites;
        });
        srvSite.publicList().then(function(result) {
            $scope.publicSites = result.sites;
        });
    }]);
});
