define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSigninApp', '$uibModal', 'srvTag', function($scope, http2, srvSigninApp, $uibModal, srvTag) {
        $scope.assignMission = function() {
            srvSigninApp.assignMission();
        };
        $scope.quitMission = function() {
            srvSigninApp.quitMission();
        };
        $scope.choosePhase = function() {
            srvSigninApp.choosePhase();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
    }]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', 'srvSigninApp', function($scope, http2, $interval, $uibModal, srvSigninApp) {
        function listReceivers(app) {
            http2.get(baseURL + 'list?site=' + app.siteid + '&app=' + app.id, function(rsp) {
                var map = { wx: '微信', yx: '易信', qy: '企业号' };
                rsp.data.forEach(function(receiver) {
                    if (receiver.sns_user) {
                        receiver.snsUser = JSON.parse(receiver.sns_user);
                        map[receiver.snsUser.src] && (receiver.snsUser.snsName = map[receiver.snsUser.src]);
                    }
                });
                $scope.receivers = rsp.data;
            });
        }

        var baseURL = '/rest/pl/fe/matter/signin/receiver/';
        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.app.siteid;
                url += '&matter_type=signinreceiver';
                url += '&matter_id=' + $scope.app.id;
                http2.get(url, function(rsp) {
                    var qrcode = rsp.data,
                        eleQrcode = $("#" + snsName + "Qrcode");
                    eleQrcode.trigger('show');
                    $scope.qrcodeURL = qrcode.pic;
                    $scope.qrcodeShown = true;
                    (function() {
                        var fnCheckQrcode, url2;
                        url2 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
                        url2 += '?site=' + qrcode.siteid;
                        url2 += '&id=' + rsp.data.id;
                        url2 += '&cascaded=N';
                        fnCheckQrcode = $interval(function() {
                            http2.get(url2, function(rsp) {
                                if (rsp.data == false) {
                                    $interval.cancel(fnCheckQrcode);
                                    eleQrcode.trigger('hide');
                                    $scope.qrcodeShown = false;
                                    (function() {
                                        var fnCheckReceiver;
                                        fnCheckReceiver = $interval(function() {
                                            http2.get('/rest/pl/fe/matter/signin/receiver/afterJoin?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&timestamp=' + qrcode.create_at, function(rsp) {
                                                if (rsp.data.length) {
                                                    $interval.cancel(fnCheckReceiver);
                                                    $scope.receivers = $scope.receivers.concat(rsp.data);
                                                }
                                            });
                                        }, 2000);
                                    })();
                                }
                            });
                        }, 2000);
                    })();
                });
            } else {
                $("#yxQrcode").trigger('hide');
                $scope.qrcodeShown = false;
            }
        };
        $scope.remove = function(receiver) {
            http2.get(baseURL + 'remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&receiver=' + receiver.userid, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        $scope.chooseQy = function() {
            $uibModal.open({
                templateUrl: 'chooseUser.html',
                controller: 'ctrlChooseUser',
            }).result.then(function(data) {
                var app = $scope.app,
                    url;
                url = '/rest/pl/fe/matter/signin/receiver/add';
                url += '?site=' + app.siteid;
                url += '&app=' + app.id;
                http2.post(url, data, function(rsp) {
                    listReceivers(app);
                });
            })
        };
        srvSigninApp.get().then(function(app) {
            listReceivers(app);
        });
    }]);
    ngApp.provider.controller('ctrlChooseUser', ['$scope', '$uibModalInstance', 'http2', 'srvSigninApp', function($scope, $mi, http2, srvSigninApp) {
        $scope.page = {
            at: 1,
            size: 15,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.search = function(name) {
            var url = '/rest/pl/fe/matter/signin/receiver/qymem';
            url += '?site=' + $scope.app.siteid;
            url += '&' + $scope.page.param();
            http2.post(url, { keyword: name }, function(rsp) {
                $scope.users = rsp.data.data;
                $scope.page.total = rsp.data.total;
            });
        }
        $scope.doSearch = function(page, name) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/signin/receiver/qymem';
            url += '?site=' + $scope.app.siteid;
            url += '&' + $scope.page.param();
            if (name) {
                http2.post(url, { keyword: name }, function(rsp) {
                    $scope.users = rsp.data.data;
                    $scope.page.total = rsp.data.total;
                })
            } else {
                http2.get(url, function(rsp) {
                    $scope.users = rsp.data.data;
                    $scope.page.total = rsp.data.total;
                });
            }
        }
        $scope.selected = [];
        var updateSelected = function(action, option) {
            if (action == 'add') {
                $scope.selected.push(option);

            }
            if (action == 'remove') {
                angular.forEach($scope.selected, function(item, index) {
                    if (item.uid == option.uid) {
                        $scope.selected.splice(index, 1);
                    }
                })
            }
        }
        $scope.updateSelection = function($event, data) {
            var checkbox = $event.target;
            var action = (checkbox.checked ? 'add' : 'remove');
            var option = {
                nickname: data.nickname,
                uid: data.userid
            };
            updateSelected(action, option);
        }
        $scope.ok = function() {
            $mi.close($scope.selected);
        };
        $scope.cancel = function() {
            $mi.dismiss();
        };
        srvSigninApp.get().then(function(app) {
            $scope.app = app;
            $scope.doSearch(1);
        });
    }]);
});