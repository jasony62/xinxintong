'use strict';

var ngMod = angular.module('task.ui.enroll', []);
ngMod.factory('enlTask', ['http2', '$q', '$parse', '$filter', '$uibModal', 'tmsLocation', function(http2, $q, $parse, $filter, $uibModal, LS) {
    function fnTaskToString() {
        var oTask = this,
            strs = [],
            min, max, limit;

        strs.push($filter('date')(oTask.start_at * 1000, 'M月d日H:mm'));
        strs.push('到')
        strs.push($filter('date')(oTask.end_at * 1000, 'M月d日H:mm'));
        min = parseInt($parse('limit.min')(oTask));
        max = parseInt($parse('limit.max')(oTask));
        if (min && max)
            limit = min + '-' + max + '个';
        else if (min)
            limit = '不少于' + min + '个';
        else if (max)
            limit = '不多于' + min + '个';
        else
            limit = '';

        switch (oTask.type) {
            case 'question':
                strs.push('，完成' + limit + '提问。');
                break;
            case 'answer':
                strs.push('，完成' + limit + '回答。');
                break;
            case 'vote':
                strs.push('，完成投票。');
                break;
            case 'score':
                strs.push('，完成打分。');
                break;
        }
        return strs.join('');
    };
    var Task;
    Task = function(oApp) {
        this.app = oApp;
    };
    Task.prototype.list = function(type, state) {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('task/list', 'site', 'app');
        if (type) url += '&type=' + type;
        if (state) url += '&state=' + state;
        http2.get(url).then(function(rsp) {
            if (rsp.data && rsp.data.length) {
                rsp.data.forEach(function(oTask) { oTask.toString = fnTaskToString; });
            }
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };

    return Task;
}]);