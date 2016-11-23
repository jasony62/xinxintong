define(['angular', "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('app', ['discuss.ui.xxt']);
    ngApp.controller('ctrlMain', ['$scope', function($scope) {
        var data = {};

        location.search.substr(1).split('&').forEach(function(param) {
            var pair = param.split('=');
            data[pair[0]] = pair[1];
        });
        data.ready = 'Y';
        $scope.data = data;
    }]);
    window.loading.finish();

    /*bootstrap*/
    angular._lazyLoadModule('app');
});