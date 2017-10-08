define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvSite', 'mediagallery', 'noticebox', 'srvApp', 'tmsThumbnail', '$timeout', 'srvTag', function($scope, $uibModal, http2, noticebox, srvSite, mediagallery, noticebox, srvApp, tmsThumbnail, $timeout, srvTag) {
        var modifiedData = {};

        $scope.modified = false;
        $scope.submit = function() {
            http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, modifiedData, function() {
                modifiedData = {};
                $scope.modified = false;
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/article/remove?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, function(rsp) {
                    if ($scope.editing.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.editing.siteid + "&id=" + $scope.editing.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.editing.siteid;
                    }
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date * 1);
                    srvApp.update('pic');
                }
            };
            mediagallery.open($scope.editing.siteid, options);
        };
        $scope.removePic = function() {
            $scope.editing.pic = '';
            srvApp.update('pic');
        };
        $scope.assignMission = function() {
            srvApp.assignMission().then(function(mission) {});
        };
        $scope.quitMission = function() {
            srvApp.quitMission().then(function() {});
        };
        $scope.choosePhase = function() {
            srvApp.choosePhase();
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            if (subType === 'C') {
                oTags = $scope.oTagC;
            } else {
                oTags = $scope.oTag;
            }
            srvTag._tagMatter($scope.editing, oTags, subType);
        };
        // 更改缩略图
        $scope.$watch('editing.title', function(title, oldTitle) {
            if ($scope.editing && title && oldTitle) {
                if (!$scope.editing.pic && title.slice(0, 1) != oldTitle.slice(0, 1)) {
                    $timeout(function() {
                        tmsThumbnail.thumbnail($scope.editing);
                    }, 3000);
                }
            }
        });
    }]);
});