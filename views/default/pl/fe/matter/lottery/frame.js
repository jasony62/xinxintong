ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', 'srvTagProvider', function($controllerProvider, $routeProvider, $locationProvider, srvTagProvider) {
    ngApp.provider = {
        controller: $controllerProvider.register
    };
    $routeProvider.when('/rest/pl/fe/matter/lottery/award', {
        templateUrl: '/views/default/pl/fe/matter/lottery/award.html?_=2',
        controller: 'ctrlAward',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/award.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/lottery/plate', {
        templateUrl: '/views/default/pl/fe/matter/lottery/plate.html?_=1',
        controller: 'ctrlPlate',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/plate.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/lottery/page', {
        templateUrl: '/views/default/pl/fe/matter/lottery/page.html?_=1',
        controller: 'ctrlPage',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/page.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/lottery/running', {
        templateUrl: '/views/default/pl/fe/matter/lottery/running.html?_=1',
        controller: 'ctrlRunning',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/running.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/lottery/result', {
        templateUrl: '/views/default/pl/fe/matter/lottery/result.html?_=1',
        controller: 'ctrlResult',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/result.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/pl/fe/matter/lottery/setting.html?_=1',
        controller: 'ctrlSetting',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/lottery/setting.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
    $locationProvider.html5Mode(true);
    //设置服务参数
    (function() {
        var ls, siteId;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        //
        srvTagProvider.config(siteId);
    })();
}]);
ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', function($scope, $location, $q, http2) {
    var ls = $location.search(),
        modifiedData = {};
    $scope.id = ls.id;
    $scope.siteId = ls.site;
    $scope.modified = false;
    $scope.awardTypes = {
        '0': {
            n: '未中奖',
            v: '0'
        },
        '1': {
            n: '用户积分',
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
    };
    $scope.submit = function() {
        var defer = $q.defer();
        http2.post('/rest/pl/fe/matter/lottery/update?site=' + $scope.siteId + '&app=' + $scope.id, modifiedData).then(function(rsp) {
            $scope.modified = false;
            modifiedData = {};
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.update = function(name) {
        modifiedData[name] = $scope.app[name];
        $scope.modified = true;
    };
    http2.get('/rest/pl/fe/matter/tag/listTags?site=' + $scope.siteId).then(function(rsp) {
        $scope.oTag = rsp.data;
    });
    http2.get('/rest/pl/fe/matter/lottery/get?site=' + $scope.siteId + '&app=' + $scope.id).then(function(rsp) {
        var app;
        app = rsp.data;
        app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
        app.type = 'group';
        $scope.persisted = angular.copy(app);
        app.awards.forEach(function(award) {
            award._type = $scope.awardTypes[award.type].n;
            award._period = $scope.awardPeriods[award.period].n;
        });
        if (app.matter_mg_tag !== '') {
            app.matter_mg_tag.forEach(function(cTag, index) {
                $scope.oTag.forEach(function(oTag) {
                    if (oTag.id === cTag) {
                        app.matter_mg_tag[index] = oTag;
                    }
                });
            });
        }
        $scope.app = app;
        $scope.url = location.protocol + '//' + location.host + '/rest/site/fe/matter/lottery?site=' + $scope.siteId + '&app=' + $scope.id;
    });
}]);