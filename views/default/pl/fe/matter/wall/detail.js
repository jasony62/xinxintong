define(['frame'], function(ngApp) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlDetail', ['$scope', 'http2', 'mediagallery', 'srvWallApp', '$uibModal', function($scope, http2, mediagallery, srvWallApp, $uibModal) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.$parent.subView = 'detail';
        $scope.tagRecordData = function(subType) {
            var oApp, oTags, tagsOfData;
            oApp = $scope.wall;
            oTags = $scope.oTag;
            $uibModal.open({
                templateUrl: 'tagMatterData.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var model;
                    $scope2.apptags = oTags;

                    if(subType === 'C'){
                        tagsOfData = oApp.matter_cont_tag;
                        $scope2.tagTitle = '内容标签';
                    }else{
                        tagsOfData = oApp.matter_mg_tag;
                        $scope2.tagTitle = '管理标签';
                    }
                    $scope2.model = model = {
                        selected: []
                    };
                    if (tagsOfData) {
                        tagsOfData.forEach(function(oTag) {
                            var index;
                            if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                                model.selected[$scope2.apptags.indexOf(oTag)] = true;
                            }
                        });
                    }
                    $scope2.createTag = function() {
                        var newTags;
                        if ($scope2.model.newtag) {
                            newTags = $scope2.model.newtag.replace(/\s/, ',');
                            newTags = newTags.split(',');
                            http2.post('/rest/pl/fe/matter/tag/create?site=' + oApp.siteid, newTags, function(rsp) {
                                rsp.data.forEach(function(oNewTag) {
                                    $scope2.apptags.push(oNewTag);
                                });
                            });
                            $scope2.model.newtag = '';
                        }
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var addMatterTag = [];
                        model.selected.forEach(function(selected, index) {
                            if (selected) {
                                addMatterTag.push($scope2.apptags[index]);
                            }
                        });
                        var url = '/rest/pl/fe/matter/tag/add?site=' + oApp.siteid + '&resId=' + oApp.id + '&resType=' + oApp.type + '&subType=' + subType;
                        http2.post(url, addMatterTag, function(rsp) {
                            if(subType === 'C'){
                                $scope.wall.matter_cont_tag = addMatterTag;
                            }else{
                                $scope.wall.matter_mg_tag = addMatterTag;
                            }
                        });
                        $mi.close();
                    };
                }],
                backdrop: 'static',
            });
        };
        $scope.start = function() {
            $scope.wall.active = 'Y';
            $scope.update('active');
        };
        $scope.end = function() {
            $scope.wall.active = 'N';
            $scope.update('active');
        };
        $scope.del = function() {
            var vcode;
            vcode = prompt('如果此信息墙中没有用户，删除后不可恢复！若要继续请输入信息墙名称');
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/remove?site=' + $scope.wall.siteid + '&app=' + $scope.wall.id, function(rsp) {
                   location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.wall.pic = url + '?_=' + (new Date() * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.wall.pic = '';
            $scope.update('pic');
        };
        $scope.assignMission = function() {
            srvWallApp.assignMission();
        };
        $scope.quitMission = function() {
            srvWallApp.quitMission();
        };
        $scope.choosePhase = function() {
            srvWallApp.choosePhase();
        };
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.wall.title + '.png"></a>')[0].click();
        };
        $scope.$watch('wall', function(oWall) {
            if (oWall) {
                $scope.entry = {
                    url: oWall.user_url,
                    qrcode: '/rest/site/fe/matter/wall/qrcode?site=' + oWall.siteid + '&url=' + encodeURIComponent(oWall.user_url),
                };
            }
        });
    }]);
});
