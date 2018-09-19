define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.service('tmsCopy', ['$http', '$q', 'tmsDynaPage', 'tmsModal', function($http, $q, tmsDynaPage, tmsModal) {
        function bySite(oMatter) {
            var url, defer;
            defer = $q.defer();
            url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    return;
                }
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }

        function copyBySite(oMatter, $aTargetSiteIds) {
            var url, defer;
            defer = $q.defer();
            url = '/rest/pl/fe/matter/article/copy?site=' + oMatter.siteid + '&id=' + oMatter.matter_id;
            $http.post(url, $aTargetSiteIds).success(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }
        this.open = function(oMatter) {
            var template;
            template = '<div class="modal-header"><span class="modal-title">指定复制位置</span></div>';
            template += '<div class="modal-body">';
            template += '<div class="checkbox" ng-repeat="site in mySites">';
            template += '<label>';
            template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'site._selected\'>';
            template += '<span>{{site.name}}</span>';
            template += '<span ng-if="site._favored===\'Y\'">（已收藏）</span>';
            template += '</label>';
            template += '</div>'
            template += '<div class="modal-footer"><button ng-click="cancel()">关闭</button><button ng-click="ok()">确定</button></div>';
            tmsModal.open({
                template: template,
                controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                    bySite(oMatter).then(function(sites) {
                        var mySites = sites;
                        mySites.forEach(function(site) {
                            site._selected = site._favored;
                        });
                        $scope2.mySites = mySites;
                    });
                    $scope2.ok = function() {
                        var result;
                        result = {
                            mySites: $scope2.mySites
                        }
                        $mi.close(result);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result.then(function(result) {
                var url, mySites;
                mySites = result.mySites;
                if (mySites) {
                    var favored = [];
                    mySites.forEach(function(site) {
                        if (site._selected !== site._favored) {
                            if (site._selected === 'Y') {
                                favored.push({ siteid: site.id });
                            }
                        }
                    });
                    if (favored.length) {
                        copyBySite(oMatter, favored);
                    }
                }
            });
        };
    }]);
    ngApp.provider.controller('ctrlFriend', ['$scope', 'http2', '$uibModal', 'noticebox', 'tmsDynaPage', 'tmsCopy', 'srvSite', function($scope, http2, $uibModal, noticebox, tmsDynaPage, tmsCopy, srvSite) {
        $scope.openSite = function(id) {
            location.href = '/rest/site/home?site=' + id;
        };
        $scope.copy = function(matter) {
            tmsCopy.open(matter);
        }
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
    }]);
    ngApp.provider.controller('ctrlSubscribeSite', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
        $scope.moreMatter = function() {
            srvSite.matterList($scope.frameState.scope, $scope.frameState.sid, $scope.pageOfmatters).then(function(result) {
                result.matters.forEach(function(matter) {
                    $scope.matters.push(matter);
                    $scope.pageOfmatters = result.page;
                });
            });
        };
        $scope.$watch('frameState.sid', function(nv) {
            srvSite.matterList('subscribeSite', nv).then(function(result) {
                $scope.matters = result.matters;
                $scope.pageOfmatters = result.page;
            });
        }, true);
    }]);
    ngApp.provider.controller('ctrlContributeSite', ['$scope', 'http2', '$q', 'srvSite', function($scope, http2, $q, srvSite) {
        $scope.close = function(m) {
            http2.get('rest/pl/fe/site/contribute/update?id=' + m.id, function(rsp) {
                $scope.matters.forEach(function(item) {
                    if (m.id == item.id) {
                        matters.splice(item, 1);
                        $scope.matters = matters;
                        $scope.pageOfmatters--;
                    }
                });
            });
        }
        $scope.moreMatter = function() {
            srvSite.matterList($scope.frameState.scope, $scope.frameState.sid, $scope.pageOfmatters).then(function(result) {
                result.matters.forEach(function(matter) {
                    $scope.matters.push(matter);
                    $scope.pageOfmatters = result.page;
                });
            });
        };
        $scope.$watch('frameState.sid', function(nv) {
            srvSite.matterList('contributeSite', nv).then(function(result) {
                $scope.matters = result.matters;
                $scope.pageOfmatters = result.page;
            });
        }, true);
    }]);
    ngApp.provider.controller('ctrlFavorSite', ['$scope', 'http2', '$q', 'srvSite', function($scope, http2, $q, srvSite) {
        $scope.moreMatter = function() {
            srvSite.matterList($scope.frameState.scope, $scope.frameState.sid, $scope.pageOfmatters).then(function(result) {
                result.matters.forEach(function(matter) {
                    $scope.matters.push(matter);
                    $scope.pageOfmatters = result.page;
                });
            });
        };
        $scope.$watch('frameState.sid', function(nv) {
            srvSite.matterList('favorSite', nv).then(function(result) {
                $scope.matters = result.matters;
                $scope.pageOfmatters = result.page;
            });
        }, true);
    }]);
});
