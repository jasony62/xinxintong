'use strict';

var ngMod = angular.module('task.ui.enroll', []);
ngMod.factory('enlTask', ['http2', '$q', '$uibModal', 'tmsLocation', function(http2, $q, $uibModal, LS) {
    var Task;
    Task = function(oApp) {
        this.app = oApp;
    };
    Task.prototype.list = function(type) {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('task/list', 'site', 'app');
        if (type) url += '&type=' + type;
        http2.get(url).then(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return Task;
}]);