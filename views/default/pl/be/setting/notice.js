define(['main'], function (ngApp) {
    'use strict';
    ngApp.provider.service('serNotice', ['$q', 'http2', function ($q, http2) {
        var _baseURL = '/rest/pl/fe/site/setting/notice',
            _siteId, _baseQS;
        this.setSiteId = function (siteId) {
            _siteId = siteId;
            _baseQS = '?site=' + siteId;
        };
        this.get = function (name) {
            var defer = $q.defer(),
                url = _baseURL + '/get' + _baseQS;
            url += '&name=' + name;
            url += '&cascaded=Y';
            http2.get(url).then(function (rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.setup = function (notice, mapping) {
            var defer = $q.defer(),
                url = _baseURL + '/setup' + _baseQS;
            url += '&name=' + notice.event_name;
            url += '&mapping=' + notice.tmplmsg_config_id;
            http2.post(url, mapping).then(function (rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.clean = function (notice) {
            var defer = $q.defer(),
                url = _baseURL + '/clean' + _baseQS;
            url += '&name=' + notice.event_name;
            url += '&mapping=' + notice.tmplmsg_config_id;
            http2.get(url).then(function (rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
    }]);
    ngApp.provider.controller('ctrlNotice', ['$scope', 'http2', '$uibModal', 'serNotice', 'noticebox', function ($scope, http2, $uibModal, serNotice, noticebox) {
        var setMappingPropName = function () {
            angular.forEach($scope.config.mapping, function (pair, tmplProp) {
                var mm = $scope.config.mapping;
                switch (pair.src) {
                    case 'text':
                        mm[tmplProp].name = pair.id;
                        break;
                }
            });
        };
        serNotice.setSiteId('platform');
        http2.get('/rest/pl/fe/matter/tmplmsg/list?site=platform&cascaded=Y').then(function (rsp) {
            $scope.plTmplmsgs = rsp.data;
        });
        $scope.choose = function (name) {
            serNotice.get(name).then(function (notice) {
                $scope.editing = notice;
                if (notice.tmplmsg_config_id !== '0') {
                    $scope.config = notice.tmplmsgConfig;
                    setMappingPropName();
                } else {
                    $scope.config = {};
                }
            });
        };
        $scope.chooseTmplmsg = function () {
            $uibModal.open({
                templateUrl: 'tmplmsgSelector.html',
                backdrop: 'static',
                resolve: {
                    tmplmsgs: function () {
                        return $scope.tmplmsgs;
                    },
                    plTmplmsgs: function () {
                        return $scope.plTmplmsgs;
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'tmplmsgs', 'plTmplmsgs', function ($mi, $scope2, tmplmsgs, plTmplmsgs) {
                    $scope2.tmplmsgs = tmplmsgs;
                    $scope2.plTmplmsgs = plTmplmsgs;
                    $scope2.data = {};
                    $scope2.close = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        $mi.close($scope2.data.selected);
                    };
                }]
            }).result.then(function (tmplmsg) {
                var data = {
                    msgid: tmplmsg.id,
                    mapping: {}
                };
                serNotice.setup($scope.editing, data).then(function (config) {
                    $scope.editing.tmplmsg_config_id = config.id;
                    $scope.config = $scope.editing.tmplmsgConfig = config;
                });
            });
        };
        $scope.selectProperty = function (tmplmsgProp) {
            $uibModal.open({
                templateUrl: 'propertySelector.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function ($mi, $scope2) {
                    var data = {
                        srcProp: ''
                    };
                    $scope2.matterProps = [{
                        id: 'title',
                        name: '标题'
                    }, {
                        id: 'summary',
                        name: '摘要'
                    }, {
                        id: 'initiator',
                        name: '事件发起人'
                    }, {
                        id: 'event_at',
                        name: '事件发生时间'
                    }];
                    $scope2.timerTaskProps = [{
                        id: 'receiver',
                        name: '接收人'
                    }, {
                        id: 'page',
                        name: '页面'
                    }];
                    $scope2.data = data;
                    $scope2.changeSrcProp = function () {
                        if (data.srcProp === 'text') {
                            data.selected = {
                                name: ''
                            };
                        }
                    };
                    $scope2.close = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function (data) {
                data.selected.src = data.srcProp;
                if (data.srcProp === 'text') {
                    data.selected.id = data.selected.name;
                }
                $scope.config.mapping[tmplmsgProp.pname] = data.selected;
                $scope.save();
            });
        };
        $scope.save = function () {
            var notice = $scope.editing,
                posted = {
                    msgid: notice.tmplmsgConfig.msgid,
                    mapping: {}
                };
            angular.forEach($scope.config.mapping, function (pair, tmplProp) {
                posted.mapping[tmplProp] = {
                    src: pair.src,
                    id: pair.id,
                    name: pair.name
                };
            });
            serNotice.setup(notice, posted).then(function (config) {
                $scope.config = $scope.editing.tmplmsgConfig = config;
                setMappingPropName();
                noticebox.success('保存成功');
            });
        };
        $scope.clean = function () {
            if (window.confirm('确定清除？')) {
                serNotice.clean($scope.editing).then(function () {
                    $scope.editing.tmplmsg_config_id = '0';
                    $scope.config = $scope.editing.tmplmsgConfig = {};
                });
            }
        };
    }]);
});