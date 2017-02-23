define(['require'], function(require) {
    angular.module('service.mission', ['ui.bootstrap', 'ui.xxt', 'service.matter']).
    provider('srvMission', function() {
        var _siteId, _missionId, _oMission, _getMissionDeferred;
        this.config = function(siteId, missionId) {
            _siteId = siteId;
            _missionId = missionId;
        };
        this.$get = ['$q', '$uibModal', 'http2', 'noticebox', function($q, $uibModal, http2, noticebox) {
            var _self = {
                get: function() {
                    var url;
                    if (_getMissionDeferred) {
                        return _getMissionDeferred.promise;
                    }
                    _getMissionDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/mission/get?id=' + _missionId;
                    http2.get(url, function(rsp) {
                        _oMission = rsp.data;
                        _oMission.extattrs = (_oMission.extattrs && _oMission.extattrs.length) ? JSON.parse(_oMission.extattrs) : {};
                        _oMission.opUrl = 'http://' + location.host + '/rest/site/op/matter/mission?site=' + _oMission.siteid + '&mission=' + _oMission.id;
                        _getMissionDeferred.resolve(_oMission);
                    });

                    return _getMissionDeferred.promise;
                },
                matterCount: function() {
                    var deferred = $q.defer();
                    http2.get('/rest/pl/fe/matter/mission/matter/count?id=' + _missionId, function(rsp) {
                        deferred.resolve(parseInt(rsp.data));
                    });
                    return deferred.promise;
                },
                recordByUser: function(user) {
                    var deferred = $q.defer();
                    if (user.userid) {
                        http2.get('/rest/pl/fe/matter/mission/user/recordByUser?mission=' + _missionId + '&user=' + user.userid, function(rsp) {
                            deferred.resolve(rsp.data);
                        });
                    } else {
                        alert('无法获得有效用户信息');
                    }
                    return deferred.promise;
                },
                submit: function(modifiedData) {
                    var defer = $q.defer();
                    http2.post('/rest/pl/fe/matter/mission/setting/update?id=' + _missionId, modifiedData, function(rsp) {
                        noticebox.success('完成保存');
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                }
            }
            return _self;
        }];
    });
});
