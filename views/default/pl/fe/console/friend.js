define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlFriend', ['$scope',  'http2', '$uibModal', 'noticebox', function($scope, http2, $uibModal, noticebox) {
        var criteria2;
        $scope.criteria2 = criteria2 = {
            scope: 'subscribeSite'
        };
        $scope.changeScope = function(scope) {
            criteria2.scope = scope;
        };
    }]);
    ngApp.provider.controller('ctrlSubscribeSite', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
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
    }]);
    ngApp.provider.controller('ctrlContributeSite', ['$scope', 'http2', function($scope, http2) {

    }]);
    ngApp.provider.controller('ctrlFavorSite', ['$scope', 'http2', function($scope, http2) {

    }]);
});
