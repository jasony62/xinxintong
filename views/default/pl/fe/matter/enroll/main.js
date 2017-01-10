define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {}]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', function($scope, http2, $interval, $uibModal) {
        var baseURL = '/rest/pl/fe/matter/enroll/receiver/';
        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.siteId;
                url += '&matter_type=enrollreceiver';
                url += '&matter_id=' + $scope.id;
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
                                            http2.get('/rest/pl/fe/matter/enroll/receiver/afterJoin?site=' + $scope.siteId + '&app=' + $scope.id + '&timestamp=' + qrcode.create_at, function(rsp) {
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
            http2.get(baseURL + 'remove?site=' + $scope.siteId + '&app=' + $scope.id + '&receiver=' + receiver.userid, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        $scope.choose = function() {
            $uibModal.open({
                 templateUrl: 'chooseUser.html',
                 controller: 'chooseUserCtrl',
            }).result.then(function(data){
                var url;
                url = '/rest/pl/fe/matter/enroll/receiver/add';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                http2.post(url, data, function(rsp){
                    http2.get(baseURL + 'list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                        $scope.receivers = rsp.data;
                    });
                });
            })
        };
        ngApp.provider.controller('chooseUserCtrl', ['$scope', '$uibModalInstance', '$location', 'http2', function($scope, $mi, $location, http2) {
            var ls = $location.search();
            $scope.id = ls.id;
            $scope.siteId = ls.site;
            $scope.page = {
                at: 1,
                size: 15,
                total: 0,
                param: function() {
                    return 'page=' + this.at + '&size=' + this.size;
               }
            };
            $scope.doSearch = function(page){
                var url;
                page && ($scope.page.at = page);
                url = '/rest/pl/fe/matter/enroll/receiver/qymem';
                url += '?site=' + $scope.siteId;
                url += '&' + $scope.page.param();
                http2.get(url, function(rsp) {
                    $scope.users = rsp.data.data;
                    $scope.page.total = rsp.data.total;
                });
            }
            $scope.isSelected = function(id){
                return $scope.selected.indexOf(id)>=0;
            }
            $scope.selected = [];
            var updateSelected = function(action,option){
                if(action == 'add' && $scope.selected.indexOf(option) == -1){
                    $scope.selected.push(option);

                }
                if(action == 'remove' && $scope.selected.indexOf(option)!=-1){
                    var idx = $scope.selected.indexOf(option);
                    $scope.selected.splice(idx,1);
                }
            }
            $scope.updateSelection = function($event, data){
                var checkbox = $event.target;
                var action = (checkbox.checked ? 'add':'remove');
                var option = {
                    nickname:data.nickname,
                    uid:data.userid
                };
                updateSelected(action,option);
            }
            $scope.ok = function() {
                $mi.close($scope.selected);
            };
            $scope.cancel = function() {
                $mi.dismiss();
            };
            $scope.doSearch(1);
        }]);
        http2.get(baseURL + 'list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
            $scope.receivers = rsp.data;
        });
    }]);
});
