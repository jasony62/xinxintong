define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlDoc', ['$scope', '$location', 'http2', 'facListFilter', 'cstApp', function($scope, $location, http2, facListFilter, cstApp) {
        var _oMission, _oCriteria, hash;
        if (hash = $location.hash()) {
            $scope.matterType = hash;
        } else {
            $scope.matterType = '';
        }
        var aUnionMatterTypes;
        aUnionMatterTypes = [];
        cstApp.matterNames.docOrder.forEach(function(name) {
            aUnionMatterTypes.push({ name: name, label: cstApp.matterNames.doc[name] });
        });
        $scope.unionMatterTypes = aUnionMatterTypes;
        $scope.unionType = '';
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
                        title: _oMission.title + '-单图文'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/article?id=' + rsp.data.id + '&site=' + _oMission.siteid;
            });
        };
        $scope.addLink = function() {
            var url = '/rest/pl/fe/matter/link/create?mission=' + _oMission.id;
            url += '&title=' + _oMission.title + '-链接';
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/link?id=' + rsp.data.id + '&site=' + _oMission.siteid;
            });
        };
        $scope.addMatter = function(matterType) {
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
                case 'link':
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
                    case 'link':
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
                case 'link':
                    alert('正在建设中……');
                    return;
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
            url = '/rest/pl/fe/matter/mission/matter/list?id=' + _oMission.id;
            if (matterType === '') {
                url += '&matterType=doc';
            } else {
                url += '&matterType=' + matterType;
            }
            http2.post(url, data, function(rsp) {
                rsp.data.forEach(function(matter) {
                    matter._operator = matter.modifier_name || matter.creater_name;
                    matter._operateAt = matter.modifiy_at || matter.create_at;
                });
                $scope.matters = rsp.data;
            });
        };
        $scope.$watch('mission', function(nv) {
            if (!nv) return;
            _oMission = nv;
            $scope.$watch('unionType', function(nv) {
                var aUnionType;
                if (nv !== undefined) {
                    $scope.matterType = nv;
                    $scope.list();
                }
            });
        });
    }]);
});