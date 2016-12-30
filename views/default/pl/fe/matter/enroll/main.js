define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {}]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', function($scope, http2, $interval) {
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
        http2.get(baseURL + 'list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
            $scope.receivers = rsp.data;
        });
    }]);
});
