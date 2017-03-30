define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSubscriber', ['$scope', function($scope) {
        var catelogs = $scope.$root.catelogs;
        catelogs.splice(0, catelogs.length, { l: '个人', v: 'client' }, { l: '团队', v: 'friend' });
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
        });
    }]);
    ngApp.provider.controller('ctrlClient', ['$scope', 'srvSite', function($scope, srvSite) {
        srvSite.subscriberList('client').then(function(result) {
            $scope.subscribers = result.subscribers;
        });
    }]);
    ngApp.provider.controller('ctrlFriend', ['$scope', 'srvSite', function($scope, srvSite) {
        srvSite.subscriberList('friend').then(function(result) {
            $scope.subscribers = result.subscribers;
        });
    }]);
});
