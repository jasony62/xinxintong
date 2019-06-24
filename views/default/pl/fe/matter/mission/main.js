define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', function($scope) {}]);
    ngApp.provider.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', 'mediagallery', 'srvTag', function($scope, http2, $uibModal, mediagallery, srvTag) {
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.entry = { url: oMission.entryUrl }
        });
        $scope.remove = function() {
            if (window.confirm('确定删除项目？')) {
                http2.get('/rest/pl/fe/matter/mission/remove?id=' + $scope.mission.id).then(function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.mission.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.mission.siteid, options);
        };
        $scope.removePic = function() {
            $scope.mission.pic = '';
            $scope.update('pic');
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var prop;
            if (data.state.indexOf('mission.') === 0) {
                prop = data.state.substr(8);
                $scope.mission[prop] = data.value;
                $scope.update(prop);
            }
        });
        $scope.makePagelet = function(type) {
            $uibModal.open({
                templateUrl: $scope.frameTemplates.url('pagelet'),
                resolve: {
                    mission: function() {
                        return $scope.mission;
                    }
                },
                controller: ['$scope', '$uibModalInstance', 'mission', 'mediagallery', function($scope2, $mi, mission, mediagallery) {
                    var tinymceEditor;
                    $scope2.reset = function() {
                        tinymceEditor.setContent('');
                    };
                    $scope2.ok = function() {
                        var html = tinymceEditor.getContent();
                        tinymceEditor.remove();
                        $mi.close({
                            html: html
                        });
                    };
                    $scope2.cancel = function() {
                        tinymceEditor.remove();
                        $mi.dismiss();
                    };
                    $scope2.$on('tinymce.multipleimage.open', function(event, callback) {
                        var options = {
                            callback: callback,
                            multiple: true,
                            setshowname: true
                        };
                        mediagallery.open($scope.mission.siteid, options);
                    });
                    $scope2.$on('tinymce.instance.init', function(event, editor) {
                        var page;

                        tinymceEditor = editor;
                        page = mission[type + '_page'];
                        if (page) {
                            editor.setContent(page.html);
                        } else {
                            http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + type).then(function(rsp) {
                                mission[type + '_page_name'] = rsp.data.name;
                                page = rsp.data;
                                editor.setContent(page.html);
                            });
                        }
                    });
                }],
                size: 'lg',
                backdrop: 'static'
            }).result.then(function(result) {
                http2.post('/rest/pl/fe/matter/mission/page/update?id=' + $scope.mission.id + '&page=' + type, result).then(function(rsp) {
                    $scope.mission[type + '_page'] = rsp.data;
                });
            });
        };
        $scope.codePage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                codeName = $scope.mission[prop];
            if (codeName && codeName.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + codeName;
            } else {
                http2.get('/rest/pl/fe/matter/mission/page/create?id=' + $scope.mission.id + '&page=' + page).then(function(rsp) {
                    $scope.mission[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.mission.siteid + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.mission, oTags, subType);
        };
    }]);
});