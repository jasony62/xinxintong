'use strict';
var ngApp = angular.module('app', ['ui.bootstrap']);
ngApp.controller('ctrlApp', ['$scope', '$http', function($scope, $http) {
    var sessionId, status;
    $scope.dial = {
        'time_ready': '00:00',
        'time_ringing': '00:00',
        'time_callstart': '00:00',
        'time_callend': '00:00'
    };

    function getStatusRecievedTimeStamp(currentTimeStamp, initTimeStamp) {
        var distance = currentTimeStamp - initTimeStamp;
        if (distance < 10000) return "00:0" + parseInt(distance / 1000);
        if (distance >= 10000 && distance < 60 * 1000) return "00:" + parseInt(distance / 1000);
        if (distance >= 60 * 1000 && distance < 60 * 60 * 1000) {
            var minute = parseInt(distance / 1000 / 60);
            var left = distance - minute * 60 * 1000;
            if (minute >= 0 && minute < 10) {
                var result = "";
                if (left < 10000) result += "0" + minute + ":0" + parseInt(left / 1000);
                else result += "0" + minute + ":" + parseInt(left / 1000);
                return result;
            }
            if (minute >= 10 && minute < 60) {
                var result = "";
                if (left < 10000) result += minute + ":0" + parseInt(left / 1000);
                else result += minute + ":" + parseInt(left / 1000);
                return result;
            }
        }
        return "Time Limited!"
    }
    $scope.call = function() {
        var getSessionUrl = '/rest/demo/call?zj=01058552614' + '&bj=' + $scope.calledNum;
        $http.get(getSessionUrl).success(function(rsp) {
            sessionId = rsp.data.sessionid;

            var getStatusUrl, getAddrUrl, timer1, timer2, id;
            timer1 = setInterval(function() {
            	getStatusUrl = 'rest/demo/getCallState?sessionid=' + sessionId + '&id=' + id;
                $http.get(getStatusUrl).success(function(rsp) {
                    $scope.status = status = rsp.data[0].code;
                    id = rsp.data[0].id;
                    if (status === '9') {
                        $scope.time_ready = getStatusRecievedTimeStamp(new Date().valueOf(), initTimeStamp);
                    } else if (status === '10') {
                        $scope.time_ringing = getStatusRecievedTimeStamp(new Date().valueOf(), initTimeStamp);
                    } else if (status === '13') {
                        $scope.time_callstart = getStatusRecievedTimeStamp(new Date().valueOf(), initTimeStamp);
                    } else if (status === '15') {
                        $scope.time_callend = getStatusRecievedTimeStamp(new Date().valueOf(), initTimeStamp);
                    }
                    if ($scope.status == '15') {
                        window.clearInterval(timer1);
                    }
                });
            }, 1000);
            timer2 = setInterval(function() {
            	getAddrUrl = '/rest/demo/getCallFileUrl?sessionid=' + sessionId;
                $http.get(getAddrUrl).success(function(rsp) {
                    if (rsp.data) {
                        $scope.record = rsp.data;
                        window.clearInterval(timer2);
                    }
                });
            }, 1000);
        });

    }
}]);