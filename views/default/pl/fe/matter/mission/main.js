define(['frame'], function (ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', function ($scope) {}]);
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', 'mediagallery', 'srvTag', function ($scope, http2, $uibModal, mediagallery, srvTag) {
        $scope.$watch('mission', function (oMission) {
            if (!oMission) return;
            $scope.entry = {
                url: oMission.entryUrl
            }
        });
        $scope.remove = function () {
            if (window.confirm('确定删除项目？')) {
                http2.get('/rest/pl/fe/matter/mission/remove?id=' + $scope.mission.id).then(function (rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function () {
            var options = {
                callback: function (url) {
                    $scope.mission.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.mission.siteid, options);
        };
        $scope.removePic = function () {
            $scope.mission.pic = '';
            $scope.update('pic');
        };
        $scope.$on('xxt.tms-datepicker.change', function (event, data) {
            var prop;
            if (data.state.indexOf('mission.') === 0) {
                prop = data.state.substr(8);
                $scope.mission[prop] = data.value;
                $scope.update(prop);
            }
        });
        $scope.makePagelet = function (type) {
            $uibModal.open({
                templateUrl: $scope.frameTemplates.url('pagelet'),
                resolve: {
                    mission: function () {
                        return $scope.mission;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'mission', 'mediagallery', function ($scope2, $mi, mission, mediagallery) {
                    var tinymceEditor;
                    $scope2.reset = function () {
                        tinymceEditor.setContent('');
                    };
                    $scope2.ok = function () {
                        var html = tinymceEditor.getContent();
                        tinymceEditor.remove();
                        $mi.close({
                            html: html
                        });
                    };
                    $scope2.cancel = function () {
                        tinymceEditor.remove();
                        $mi.dismiss();
                    };
                    $scope2.$on('tinymce.multipleimage.open', function (event, callback) {
                        var options = {
                            callback: callback,
                            multiple: true,
                            setshowname: true
                        };
                        mediagallery.open($scope.mission.siteid, options);
                    });
                    $scope2.$on('tinymce.instance.init', function (event, editor) {
                        var page;

                        tinymceEditor = editor;
                        page = mission[type + '_page'];
                        if (page) {
                            editor.setContent(page.html);
                        } else {
                            http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + type).then(function (rsp) {
                                mission[type + '_page_name'] = rsp.data.name;
                                page = rsp.data;
                                editor.setContent(page.html);
                            });
                        }
                    });
                }],
                size: 'lg',
                backdrop: 'static'
            }).result.then(function (result) {
                http2.post('/rest/pl/fe/matter/mission/page/update?id=' + $scope.mission.id + '&page=' + type, result).then(function (rsp) {
                    $scope.mission[type + '_page'] = rsp.data;
                });
            });
        };
        $scope.assignUserApp = function () {
            var mission = $scope.mission;
            $uibModal.open({
                templateUrl: 'assignUserApp.html',
                controller: ['$scope', '$uibModalInstance', 'srvSite', function ($scope2, $mi, srvSite) {
                    $scope2.data = {
                        appId: '',
                        appType: 'group'
                    };
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        $mi.close($scope2.data);
                    };
                    $scope2.$watch('data.appType', function (appType) {
                        if (appType) {
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList(mission, true).then(function (aMemberSchemas) {
                                    $scope2.apps = aMemberSchemas;
                                });
                            } else {
                                var url = '/rest/pl/fe/matter/' + appType + '/list?mission=' + mission.id;
                                http2.get(url).then(function (rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                        }
                    });
                }],
                backdrop: 'static'
            }).result.then(function (data) {
                mission.user_app_id = data.appId;
                mission.user_app_type = data.appType;
                $scope.update(['user_app_id', 'user_app_type']).then(function (rsp) {
                    if (data.appType === 'mschema') {
                        var url = '/rest/pl/fe/matter/mission/get?id=' + mission.id;
                        http2.get(url).then(function (rsp) {
                            mission.userApp = rsp.data.userApp;
                        });
                    } else {
                        var key = data.appType == 'enroll' ? 'app' : 'id';
                        var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&' + key + '=' + data.appId;
                        http2.get(url).then(function (rsp) {
                            mission.userApp = rsp.data;
                            if (mission.userApp.data_schemas && angular.isString(mission.userApp.data_schemas)) {
                                mission.userApp.data_schemas = JSON.parse(mission.userApp.data_schemas);
                            }
                        });
                    }
                });
            });
        };
        $scope.cancelUserApp = function () {
            var mission;
            if (window.confirm('确定删除项目用户名单活动？')) {
                mission = $scope.mission;
                mission.user_app_id = '';
                mission.user_app_type = '';
                $scope.update(['user_app_id', 'user_app_type']).then(function () {
                    delete mission.userApp;
                });
            }
        };
        $scope.codePage = function (event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                codeName = $scope.mission[prop];
            if (codeName && codeName.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + codeName;
            } else {
                http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + page).then(function (rsp) {
                    $scope.mission[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.tagMatter = function (subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.mission, oTags, subType);
        };
    }]);
});