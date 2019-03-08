define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['cstApp', '$scope', 'http2', '$q', 'srvSite', 'noticebox', 'srvGroupApp', '$uibModal', 'srvTag', function(cstApp, $scope, http2, $q, srvSite, noticebox, srvGrpApp, $uibModal, srvTag) {
        $scope.update = function(names) {
            srvGrpApp.update(names).then(function(rsp) {
                noticebox.success('完成保存');
            });
        };
        $scope.setPic = function() {
            var oOptions = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, oOptions);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            $scope.update('pic');
        };
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.app.title + '-二维码.png"></a>')[0].click();
        };
        $scope.assocWithApp = function() {
            srvGrpApp.assocWithApp(cstApp.importSource).then(function(data) {
                $scope.chooseAssocWitchApp = data;
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            $scope.update(data.state);
        });
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/group/remove?app=' + $scope.app.id).then(function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.assignMission = function() {
            srvSite.openGallery({
                matterTypes: [{
                    value: 'mission',
                    title: '项目',
                    url: '/rest/pl/fe/matter'
                }],
                hasParent: false,
                singleMatter: true
            }).then(function(result) {
                var app;
                if (result.matters.length === 1) {
                    app = {
                        id: $scope.app.id,
                        type: 'group'
                    };
                    http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.app.siteid + '&id=' + result.matters[0].id, app).then(function(rsp) {
                        $scope.app.mission = rsp.data;
                        $scope.app.mission_id = rsp.data.id;
                        srvGrpApp.update('mission_id');
                    });
                }
            });
        };
        $scope.quitMission = function() {
            if (window.confirm('确定将[' + $scope.app.title + ']从项目中移除？')) {
                var oApp = $scope.app,
                    matter = {
                        id: oApp.id,
                        type: 'group',
                        title: oApp.title
                    };
                http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + oApp.siteid + '&id=' + oApp.mission_id, matter).then(function(rsp) {
                    delete oApp.mission;
                    oApp.mission_id = 0;
                    srvGrpApp.update(['mission_id']);
                });
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
    }]);
});