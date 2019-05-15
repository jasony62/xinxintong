'use strict';

var ngMod = angular.module('history.ui.enroll', []);
ngMod.factory('enlHistory', ['http2', '$q', '$uibModal', 'tmsLocation', function (http2, $q, $uibModal, LS) {
    var History;
    History = function () {};
    History.prototype.show = function () {
        var _self = this;
        return $uibModal.open({
            template: require('./history.html'),
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.ok = function () {};
                $scope2.cancel = function () {
                    $mi.dismiss('cancel');
                };
            }]
        }).result;
    };
    return History;
}]);