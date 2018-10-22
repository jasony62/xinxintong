define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSubscriber', ['$scope', function($scope) {
        var catelogs;
        $scope.catelogs = catelogs = [];
        catelogs.splice(0, catelogs.length, { l: '个人', v: 'client' }, { l: '团队', v: 'friend' });
        $scope.catelog = catelogs[0];
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