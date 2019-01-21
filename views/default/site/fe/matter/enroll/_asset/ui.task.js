'use strict';

var ngMod = angular.module('task.ui.enroll', []);
ngMod.factory('enlTask', ['http2', '$q', '$parse', '$filter', '$uibModal', 'tmsLocation', function(http2, $q, $parse, $filter, $uibModal, LS) {
    var i18n = {
        weekday: {
            'Mon': '周一',
            'Tue': '周二',
            'Wed': '周三',
            'Thu': '周四',
            'Fri': '周五',
            'Sat': '周六',
            'Sun': '周日',
        }
    };

    function fnTaskToString() {
        var oTask = this,
            strs = [],
            min, max, limit, str, weekday, oDateFilter;

        oDateFilter = $filter('date');
        str = oDateFilter(oTask.start_at * 1000, 'M月d日H:mm（EEE）');
        weekday = oDateFilter(oTask.start_at * 1000, 'EEE');
        str = str.replace(weekday, i18n.weekday[weekday]);
        strs.push(str, '到');
        str = oDateFilter(oTask.end_at * 1000, 'M月d日H:mm（EEE）');
        weekday = oDateFilter(oTask.end_at * 1000, 'EEE');
        str = str.replace(weekday, i18n.weekday[weekday]);
        strs.push(str);
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
    Task.prototype.list = function(type, state, rid) {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('task/list', 'site', 'app');
        if (type) url += '&type=' + type;
        if (state) url += '&state=' + state;
        if (rid) url += '&rid=' + rid;
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