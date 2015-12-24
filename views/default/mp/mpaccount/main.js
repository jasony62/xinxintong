xxtApp.controller('mainCtrl', ['$rootScope', '$scope', 'http2', '$timeout', function($rootScope, $scope, http2, $timeout) {
    var checkJoin = function() {
        if ($scope.stateOfCheckJoin) {
            http2.get($scope.stateOfCheckJoin.url, function(rsp) {
                if (rsp.data === 'N') {
                    $scope.stateOfCheckJoin.count++;
                    $timeout(checkJoin, 10000);
                } else {
                    $scope.mpa[$scope.mpa.mpsrc + '_joined'] = 'Y';
                    $scope.stateOfCheckJoin = false;
                }
            });
        }
    };
    $scope.mpsrcs = [{
        'l': '微信公众号',
        'v': 'wx'
    }, {
        'l': '易信公众号',
        'v': 'yx'
    }, {
        'l': '微信企业号',
        'v': 'qy'
    }];
    $scope.backRunning = false;
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.mpa[name];
        http2.post('/rest/mp/mpaccount/update', p, function(rsp) {
            if (name === 'token') {
                $scope.mpa.wx_joined = 'N';
                $scope.mpa.yx_joined = 'N';
                $scope.mpa.qy_joined = 'N';
            }
        });
    };
    $scope.stateOfCheckJoin = false;
    $scope.checkJoin = function() {
        if ($scope.stateOfCheckJoin) {
            $scope.stateOfCheckJoin = false;
        } else {
            $scope.stateOfCheckJoin = {
                running: true,
                count: 0,
                url: '/rest/mp/mpaccount/checkJoin'
            };
            checkJoin();
        }
    };
    $scope.reset = function(name) {
        $scope.mpa[name] = 'N';
        $scope.update(name);
    };
    $scope.updateApi = function(name) {
        var p = {};
        p[name] = $scope.mpapis[name];
        http2.post('/rest/mp/mpaccount/updateApi', p);
    };
    $scope.refreshFans = function() {
        $rootScope.progmsg = '开始更新';
        $scope.backRunning = true
        var finish = 0;
        var doRefresh = function(step, nextOpenid) {
            var url, params;
            url = '/rest/mp/user/fans/refreshAll';
            params = [];
            step && params.push('step=' + step);
            nextOpenid && params.push('nextOpenid=' + nextOpenid);
            params.length && (url += '?' + params.join('&'));
            http2.get(url, function(rsp) {
                if (angular.isObject(rsp) && rsp.err_code === 0) {
                    if (rsp.data.left > 0) {
                        doRefresh(rsp.data.step, rsp.data.nextOpenid);
                    } else if (rsp.data.nextOpenid) {
                        doRefresh(0, rsp.data.nextOpenid);
                    } else {
                        $scope.backRunning = false;
                    }
                    finish += rsp.data.finish;
                    $rootScope.progmsg = '更新数量：' + finish + '/' + rsp.data.total;
                }
            }, {
                autoBreak: false
            });
        };
        doRefresh(0);
    };
    $scope.refreshFansGroup = function() {
        $scope.backRunning = true;
        http2.get('/rest/mp/user/fans/refreshGroup', function(rsp) {
            $scope.backRunning = false;
            $rootScope.progmsg = '更新用户分组数量：' + rsp.data;
        });
    };
    $scope.genParent = function() {
        var vcode;
        vcode = prompt('是否要生成当前公众号的父账号？，若是，请输入公众号名称。');
        if (vcode === $scope.mpa.name) {
            http2.get('/rest/mp/mpaccount/genParent', function(rsp) {
                location.replace('/rest/mp/mpaccount?mpid=' + rsp.data);
            });
        }
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.mpa.qrcode = url + '?_=' + (new Date()) * 1;
                $scope.update('qrcode');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.mpa.qrcode = '';
        $scope.update('qrcode');
    };
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            $scope.mpa = params.mpaccount;
            $scope.mpapis = params.apis;
        }
    });
}]);