define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlDoc', ['$scope', '$location', 'http2', 'facListFilter', function($scope, $location, http2, facListFilter) {
        var _oMission, _oCriteria;
        $scope.matterType = $location.hash();
        if (!/article/.test($scope.matterType)) {
            $scope.matterType = '';
        }
        $scope.criteria = _oCriteria = {
            pid: 'ALL',
            filter: {}
        };
        $scope.filter = facListFilter.init(function() {
            $scope.list();
        }, _oCriteria.filter);
        $scope.addArticle = function() {
            var url = '/rest/pl/fe/matter/article/create?mission=' + _oMission.id,
                config = {
                    proto: {
                        title: _oMission.title + '-资料'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/article?id=' + rsp.data.id + '&site=' + _oMission.siteid;
            });
        };
        $scope.addMatter = function(matterType) {
            if (!matterType) {
                matterType = $scope.matterType;
            }
            $scope['add' + matterType[0].toUpperCase() + matterType.substr(1)]();
        };
        $scope.openMatter = function(matter, subView) {
            var url = '/rest/pl/fe/matter/',
                type = matter.type || $scope.matterType,
                id = matter.id;
            url += type;
            if (subView) {
                url += '/' + subView;
            }
            switch (type) {
                case 'article':
                    location.href = url + '?id=' + id + '&site=' + _oMission.siteid;
                    break;
            }
        };
        $scope.removeMatter = function(evt, matter) {
            var type = matter.type || $scope.matterType,
                id = matter.id,
                title = matter.title,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            if (window.confirm('确定删除：' + title + '？')) {
                switch (type) {
                    case 'article':
                        url += type + '/remove?id=' + id + '&site=' + _oMission.siteid;
                        break;
                }
                http2.get(url, function(rsp) {
                    $scope.matters.splice($scope.matters.indexOf(matter), 1);
                });
            }
        };
        $scope.copyMatter = function(evt, matter) {
            var type = (matter.type || $scope.matterType),
                id = matter.id,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            switch (type) {
                case 'article':
                    url += type + '/copy?id=' + id + '&site=' + _oMission.siteid + '&mission=' + _oMission.id;
                    break;
            }
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/' + type + '?site=' + _oMission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.list = function() {
            var url, data, matterType;

            data = {};
            if (_oCriteria.pid) {
                data.mission_phase_id = _oCriteria.pid;
            }
            if (_oCriteria.filter.by === 'title') {
                data.byTitle = _oCriteria.filter.keyword;
            }
            matterType = $scope.matterType;
            if (matterType === '') {
                url = '/rest/pl/fe/matter/mission/matter/list?id=' + _oMission.id;
                url += '&matterType=doc';
                http2.post(url, data, function(rsp) {
                    rsp.data.forEach(function(matter) {
                        matter._operator = matter.modifier_name || matter.creater_name;
                        matter._operateAt = matter.modifiy_at || matter.create_at;
                    });
                    $scope.matters = rsp.data;
                });
            } else {
                url = '/rest/pl/fe/matter/';
                url += matterType;
                url += '/list?mission=' + _oMission.id;
                http2.post(url, data, function(rsp) {
                    $scope.matters = rsp.data.docs;
                });
            }
        };
        $scope.$watch('mission', function(nv) {
            if (!nv) return;
            _oMission = nv;
            $scope.$watch('matterType', function(nv) {
                $scope.list();
            });
        });
    }]);
});