define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$uibModal', 'noticebox', 'srvSite', 'srvEnrollApp', 'srvTag', function($scope, http2, $uibModal, noticebox, srvSite, srvEnrollApp, srvTag) {
        $scope.assignMission = function() {
            srvEnrollApp.assignMission().then(function(mission) {});
        };
        $scope.tagMatter = function(subType){
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
        $scope.quitMission = function() {
            srvEnrollApp.quitMission().then(function() {});
        };
        $scope.choosePhase = function() {
            srvEnrollApp.choosePhase();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除活动？')) {
                srvEnrollApp.remove().then(function() {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.exportAsTemplate = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/exportAsTemplate?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            window.open(url);
        };
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.app.siteid + '&type=enroll&id=' + $scope.app.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        };
    }]);
});
