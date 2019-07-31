'use strict';
/**
 * 页面事件追踪
 */
var ngMod = angular.module('protect.ui.xxt', ['http.ui.xxt']);
ngMod.directive('tmsProtect', ['$q', '$timeout', 'http2', '$uibModal', function($q, $timeout, http2, $uibModal) {
    var modalEle;
    var ProtectKey = 'xxt.pl.protect.system';
    var StoreKey = 'xxt.pl.protect.event.trace';
    var TraceStack = function() {
        function storeTrace(time) {
            var oStorage, oCached;
            if (oStorage = window.localStorage) {
                oCached = oStorage.getItem(StoreKey);
                oCached = oCached ? JSON.parse(oCached) : {};
                oCached.lasttime = time;
                oStorage.setItem(StoreKey, JSON.stringify(oCached));
            }
        };

        function getLastTime() {
            var oStorage, oCached;
            if (oStorage = window.localStorage) {
                oCached = oStorage.getItem(StoreKey);
                oCached = oCached ? JSON.parse(oCached) : {};
                return oCached.lasttime || new Date() * 1;
            }
        };

        function validPwd() {
            var template;
            template = '<div class="modal-body">';
            template += '<div class="form-group">';
            template += '<label class="control-label">由于您长时间没有操作，请重新输入登录密码</label>';
            template += '<p><input type=\'password\' class="form-control" ng-model=\'user.password\' ng-focus=\'msg=""\'></p>';
            template += '<p style="color:red;font-size:12px" ng-bind=\'msg\'></p>';
            template += '</div>';
            template += '<div class="text-right"><button class="btn btn-success" ng-click="ok()">确定</button></div>';
            template += '</div>';
            $uibModal.open({
                template: template,
                controller: ['$scope', '$uibModalInstance', '$http', function($scope2, $mi, $http) {
                    $scope2.user = { password: "" };
                    $scope2.msg = "";
                    $scope2.ok = function() {
                        $http.post("/rest/site/fe/user/login/validatePwd", $scope2.user).then(function(rsp) {
                            if (!rsp.data.err_code) {
                                $mi.close();
                            } else {
                                $scope2.msg = rsp.data.err_msg;
                            }
                        });
                    };
                }],
                backdrop: 'static',
                size: 'sm'
            }).result.then(function() {
                var confirmTime = new Date() * 1;
                storeTrace(confirmTime);
            });
        };

        this.occurEvent = function(timer) {
            var currentTime = new Date() * 1;
            var lasttime = getLastTime();
            (currentTime - lasttime) > timer ? validPwd() : storeTrace(currentTime);
        };

        this.getStorage = function() {
            var oStorage, oCached, lasttime;
            if (oStorage = window.localStorage) {
                oCached = oStorage.getItem(StoreKey);
                if (oCached) {
                    oCached = JSON.parse(oCached);
                    lasttime = oCached.lasttime;
                }
            }
        };
    };

    var oSeesionStorage, oSessionCached, intervaltime;
    if (oSeesionStorage = window.sessionStorage) {
        oSessionCached = oSeesionStorage.getItem(ProtectKey);
        if (oSessionCached) {
            oSessionCached = JSON.parse(oSessionCached);
            intervaltime = oSessionCached.noHookMaxTime * 60 * 1000;
        } else {
            oSessionCached = {};
            $.ajax({
                "url": "/tmsappconfig.php",
                async: false,
                success: function(result) {
                    intervaltime = result.noHookMaxTime * 60 * 1000;
                    oSeesionStorage.setItem(ProtectKey, JSON.stringify(result));
                }
            });
        }
    }

    return {
        restrict: 'A',
        link: function(scope, elem, attr) {
            if (!oSessionCached.noHookMaxTime && oSessionCached.noHookMaxTime <= 0) {
                return false;
            }
            var oTraceStack = new TraceStack();
            oTraceStack.getStorage();

            /* 打开页面 */
            oTraceStack.occurEvent(intervaltime);
            /* 用户点击页面 */
            elem.on('click', function(event) {
                if (!document.getElementsByClassName('modal')[0]) {
                    oTraceStack.occurEvent(intervaltime);
                }
            });
            /* 用户滚动页面 */
            document.addEventListener('scroll', function(event) {
                oTraceStack.occurEvent(intervaltime);
            });
        }
    }
}]);