define(['require'], function(require) {
    'use strict';
    /**
     * srvWallApp
     */
    angular.module('service.wall', ['ui.bootstrap', 'ui.xxt', 'service.matter']).
    provider('srvWallApp', function() {
        var _siteId, _wallId, _oWall, _getAppDeferred;

        this.app = function() {
            return _oWall;
        };
        this.config = function(siteId, appId) {
            _siteId = siteId;
            _wallId = appId;
        }
        this.$get = ['$q', 'http2', 'noticebox', 'srvSite', '$uibModal', function($q, http2, noticebox, srvSite, $uibModal) {
            return {
                get: function() {
                    var url;

                    if (_getAppDeferred) {
                        return _getAppDeferred.promise;
                    }
                    _getAppDeferred = $q.defer();
                    url = '/rest/pl/fe/matter/wall/get?site=' + _siteId + '&id=' + _wallId;
                    http2.get(url).then(function(rsp) {
                        _oWall = rsp.data;
                        _getAppDeferred.resolve(_oWall);
                    });

                    return _getAppDeferred.promise;
                },
                update: function(names) {
                    var defer = $q.defer(),
                        modifiedData = {},
                        url;

                    angular.isString(names) && (names = [names]);
                    names.forEach(function(name) {
                        modifiedData[name] = _oWall[name];
                    });
                    url = '/rest/pl/fe/matter/wall/update?site=' + _siteId + '&app=' + _wallId;
                    http2.post(url, modifiedData).then(function(rsp) {
                        defer.resolve(rsp.data);
                    });
                    return defer.promise;
                },
                assignMission: function() {
                    var _this = this;
                    srvSite.openGallery({
                        matterTypes: [{
                            value: 'mission',
                            title: '项目',
                            url: '/rest/pl/fe/matter'
                        }],
                        singleMatter: true
                    }).then(function(missions) {
                        var matter;
                        if (missions.matters.length === 1) {
                            matter = {
                                id: _wallId,
                                type: 'wall'
                            };
                            http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + _siteId + '&id=' + missions.matters[0].id, matter).then(function(rsp) {
                                var mission = rsp.data,
                                    updatedFields = ['mission_id'];

                                _oWall.mission = mission;
                                _oWall.mission_id = mission.id;
                                if (!_oWall.pic || _oWall.pic.length === 0) {
                                    _oWall.pic = mission.pic;
                                    updatedFields.push('pic');
                                }
                                if (!_oWall.summary || _oWall.summary.length === 0) {
                                    _oWall.summary = mission.summary;
                                    updatedFields.push('summary');
                                }
                                _this.update(updatedFields);
                            });
                        }
                    });
                },
                quitMission: function() {
                    var _this = this,
                        matter = {
                            id: _wallId,
                            type: 'wall',
                            title: _oWall.title
                        };
                    http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + _siteId + '&id=' + _oWall.mission_id, matter).then(function(rsp) {
                        delete _oWall.mission;
                        _oWall.mission_id = null;
                        _this.update(['mission_id']);
                    });
                },
            };
        }];
    });
});