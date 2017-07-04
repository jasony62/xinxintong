define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTag',['$scope', '$http', function($scope, $http) {
        $scope.page = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        }
        $scope.create = function() {
            console.log(1);
        }
    }]);
});
