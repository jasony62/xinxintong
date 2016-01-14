xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/mp/app/lottery/award', {
        templateUrl: '/views/default/mp/app/lottery/award.html?_=1',
        controller: 'awardCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/lottery/award.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/lottery/plate', {
        templateUrl: '/views/default/mp/app/lottery/plate.html?_=1',
        controller: 'plateCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/lottery/plate.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/lottery/page', {
        templateUrl: '/views/default/mp/app/lottery/page.html?_=1',
        controller: 'pageCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/lottery/page.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/lottery/result', {
        templateUrl: '/views/default/mp/app/lottery/result.html?_=1',
        controller: 'resultCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/lottery/result.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/mp/app/lottery/setting.html?_=2',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/lottery/setting.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
}]);
xxtApp.controller('lotteryCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.awardTypes = {
        '0': {
            n: '未中奖',
            v: '0'
        },
        '1': {
            n: '应用积分',
            v: '1'
        },
        '2': {
            n: '奖励重玩',
            v: '2'
        },
        '3': {
            n: '完成任务',
            v: '3'
        },
        '99': {
            n: '实体奖品',
            v: '99'
        }
    };
    $scope.awardPeriods = {
        'A': {
            n: '总计',
            v: 'A'
        },
        'D': {
            n: '每天',
            v: 'D'
        },
    }
    $scope.lid = $location.search().lottery;
    $scope.subView = 'setting';
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        http2.get('/rest/mp/app/lottery/get?lottery=' + $scope.lid, function(rsp) {
            var lottery, i, l, award;
            lottery = rsp.data;
            lottery._url = 'http://' + location.host + "/rest/app/lottery?mpid=" + $scope.mpaccount.mpid + "&lottery=" + $scope.lid;
            lottery._pretaskdoneUrl = 'http://' + location.host + "/rest/app/lottery?mpid=" + $scope.mpaccount.mpid + "&lottery=" + $scope.lid + "&pretaskdone=Y";
            if (lottery.awards === undefined) {
                lottery.awards = [];
            } else {
                for (i = 0, l = lottery.awards.length; i < l; i++) {
                    award = lottery.awards[i];
                    award._type = $scope.awardTypes[award.type].n;
                    award._period = $scope.awardPeriods[award.period].n;
                }
            }
            $scope.lottery = lottery;
        });
    });
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.lottery[name];
        http2.post('/rest/mp/app/lottery/update?lottery=' + $scope.lid, p);
    };
}]);