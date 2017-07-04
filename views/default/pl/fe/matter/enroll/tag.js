define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTag',['$scope', '$http', function($scope, $http) {
        $scope.scopeNames = {
            'U': '参与者',
            'I': '发起人'
        };
        $scope.page = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.create = function() {
            console.log(1);
        };
        $scope.doSearch = function(page) {

        };
        $scope.edit = function(tag, index) {

        };
        $scope.remove = function(tag, index) {

        };
        $scope.up = function(tag, index) {

        };
        $scope.down = function(tag, index) {

        };
    }]);
});
