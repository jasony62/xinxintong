xxtApp.factory('Mp', function ($q, http2) {
    var Mp = function () {
    };
    Mp.prototype.getAuthapis = function (id) {
        var _this = this, deferred = $q.defer(), promise = deferred.promise;
        if (_this.authapis !== undefined) {
            deferred.resolve(_this.authapis);
        } else {
            http2.get('/rest/mp/authapi/get?valid=Y', function (rsp) {
                _this.authapis = rsp.data;
                deferred.resolve(rsp.data);
            });
        }
        return promise;
    };
    return Mp;
});
xxtApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/rest/mp/app/enroll/detail', {
        templateUrl: '/views/default/mp/app/enroll/setting.html',
        controller: 'settingCtrl',
        resolve: {
            load: function ($q) {
                var defer = $q.defer();
                (function () { $.getScript('/views/default/mp/app/enroll/setting.js', function () { defer.resolve(); }); })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/enroll/round', {
        templateUrl: '/views/default/mp/app/enroll/round.html',
        controller: 'roundCtrl'
    }).when('/rest/mp/app/enroll/page', {
        templateUrl: '/views/default/mp/app/enroll/page.html',
        controller: 'pageCtrl',
        resolve: {
            load: function ($q) {
                var defer = $q.defer();
                (function () { $.getScript('/views/default/mp/app/enroll/page.js', function () { defer.resolve(); }); })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/enroll/record', {
        templateUrl: '/views/default/mp/app/enroll/record.html',
        controller: 'recordCtrl',
        resolve: {
            load: function ($q) {
                var defer = $q.defer();
                (function () { $.getScript('/views/default/mp/app/enroll/record.js', function () { defer.resolve(); }); })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/enroll/stat', {
        templateUrl: '/views/default/mp/app/enroll/stat.html',
        controller: 'statCtrl'
    }).when('/rest/mp/app/enroll/accesslog', {
        templateUrl: '/views/default/mp/app/enroll/accesslog.html',
        controller: 'accesslogCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/enroll/setting.html',
        controller: 'settingCtrl'
    });
}]);
xxtApp.controller('enrollCtrl', ['$scope', '$location', 'http2', function ($scope, $location, http2) {
    $scope.aid = $location.search().aid;
    $scope.subView = '';
    $scope.taskCodeEntryUrl = 'http://' + location.host + '/rest/q';
    $scope.$root.floatToolbar = { matterShop: true };
    $scope.canRounds = function () {
        $scope.editing.multi_rounds = 'Y';
        $scope.editing.rounds = [];
        $location.path('/rest/mp/app/enroll/round');
    };
    $scope.update = function (name) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var p = {};
            if (name === 'entry_rule')
                p.entry_rule = encodeURIComponent($scope.editing[name]);
            else if (name === 'tags')
                p.tags = $scope.editing.tags.join(',');
            else
                p[name] = $scope.editing[name];
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.$on('xxt.float-toolbar.shop.open', function (event) {
        $scope.$emit('mattershop.new', $scope.mpid, $scope.editing);
    });
    http2.get('/rest/mp/app/enroll/get?aid=' + $scope.aid, function (rsp) {
        $scope.editing = rsp.data;
        $scope.editing.tags = (!$scope.editing.tags || $scope.editing.tags.length === 0) ? [] : $scope.editing.tags.split(',');
        $scope.editing.type = 'enroll';
        $scope.editing.pages.form.title = '登记信息页';
        $scope.editing.canSetReceiver = 'Y';
        $scope.persisted = angular.copy($scope.editing);
        $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.editing.mpid;
    });
    http2.get('/rest/mp/mpaccount/get', function (rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = $scope.mpaccount.parent_mpid && $scope.mpaccount.parent_mpid.length;
    });
    http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function (rsp) {
        $scope.features = rsp.data;
    });
}]);
xxtApp.controller('roundCtrl', ['$scope', '$modal', 'http2', function ($scope, $modal, http2) {
    $scope.$parent.subView = 'round';
    $scope.roundState = ['新建', '启用', '停止'];
    $scope.add = function () {
        $modal.open({
            templateUrl: 'roundEditor.html',
            backdrop: 'static',
            resolve: {
                roundState: function () { return $scope.roundState; }
            },
            controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                $scope.round = { state: 0 };
                $scope.roundState = roundState;
                $scope.close = function () { $modalInstance.dismiss(); };
                $scope.ok = function () { $modalInstance.close($scope.round); };
                $scope.start = function () {
                    $scope.round.state = 1;
                    $modalInstance.close($scope.round);
                };
            }]
        }).result.then(function (newRound) {
            http2.post('/rest/mp/app/enroll/round/add?aid=' + $scope.aid, newRound, function (rsp) {
                if ($scope.editing.rounds.length > 0 && rsp.data.state == 1)
                    $scope.editing.rounds[1].state = 2;
                $scope.editing.rounds.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.open = function (round) {
        $modal.open({
            templateUrl: 'roundEditor.html',
            backdrop: 'static',
            resolve: {
                roundState: function () { return $scope.roundState; }
            },
            controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                $scope.round = angular.copy(round);
                $scope.roundState = roundState;
                $scope.close = function () { $modalInstance.dismiss(); };
                $scope.ok = function () { $modalInstance.close({ action: 'update', data: $scope.round }); };
                $scope.remove = function () { $modalInstance.close({ action: 'remove' }); };
                $scope.start = function () {
                    $scope.round.state = 1;
                    $modalInstance.close({ action: 'update', data: $scope.round });
                };
            }]
        }).result.then(function (rst) {
            var url;
            if (rst.action === 'update') {
                url = '/rest/mp/app/enroll/round/update';
                url += '?aid=' + $scope.aid;
                url += '&rid=' + round.rid;
                http2.post(url, rst.data, function (rsp) {
                    if ($scope.editing.rounds.length > 1 && rst.data.state == 1)
                        $scope.editing.rounds[1].state = 2;
                    angular.extend(round, rst.data);
                });
            } else if (rst.action === 'remove') {
                url = '/rest/mp/app/enroll/round/remove';
                url += '?aid=' + $scope.aid;
                url += '&rid=' + round.rid;
                http2.get(url, function (rsp) {
                    var i = $scope.editing.rounds.indexOf(round);
                    $scope.editing.rounds.splice(i, 1);
                });
            }
        });
    };
}]);
xxtApp.controller('statCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.$parent.subView = 'stat';
    http2.get('/rest/mp/app/enroll/statGet?aid=' + $scope.aid, function (rsp) {
        $scope.stat = rsp.data;
    });
}]);
xxtApp.controller('accesslogCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.$parent.subView = 'accesslog';
}]);
xxtApp.controller('lotteryCtrl', ['$scope', 'http2', function ($scope, http2) {
    var getWinners = function () {
        var url = '/rest/mp/app/enroll/lotteryWinners?aid=' + $scope.aid;
        if ($scope.editing)
            url += '&rid=' + $scope.editing.round_id;
        http2.get(url, function (rsp) {
            $scope.winners = rsp.data;
        });
    };
    $scope.aTargets = null;
    $scope.addRound = function () {
        http2.post('/rest/mp/app/enroll/addLotteryRound?aid=' + $scope.aid, null, function (rsp) {
            $scope.rounds.push(rsp.data);
        });
    };
    $scope.open = function (round) {
        $scope.editing = round;
        $scope.aTargets = $scope.editing.targets.length === 0 ? [] : eval($scope.editing.targets);
        getWinners();
    };
    $scope.updateLotteryRound = function (name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/enroll/updateLotteryRound?aid=' + $scope.aid + '&rid=' + $scope.editing.round_id, nv, function (rsp) {
        });
    };
    $scope.removeLotteryRound = function () {
        http2.post('/rest/mp/app/enroll/removeLotteryRound?aid=' + $scope.aid + '&rid=' + $scope.editing.round_id, null, function (rsp) {
            var i = $scope.rounds.indexOf($scope.editing);
            $scope.rounds.splice(i, 1);
        });
    };
    $scope.addTarget = function () {
        var target = { tags: [] };
        $scope.aTargets.push(target);
    };
    $scope.removeTarget = function (i) {
        $scope.aTargets.splice(i, 1);
    };
    $scope.saveTargets = function () {
        var arr = [];
        for (var i in $scope.aTargets)
            arr.push({ tags: $scope.aTargets[i].tags });
        $scope.editing.targets = JSON.stringify(arr);
        $scope.updateLotteryRound('targets');
    };
    $scope.$on('tag.xxt.combox.done', function (event, aSelected, state) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.aTargets[state].tags) {
                if (aSelected[i] === $scope.aTargets[state].tags[j]) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag, state) {
        $scope.aTargets[state].tags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
            $scope.editing.tags = $scope.aTags.join(',');
            $scope.update('tags');
        }
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed, state) {
        $scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
    });
    $scope.aTags = $scope.editing.tags.length === 0 ? [] : $scope.editing.tags.split(',');
    $scope.lotteryUrl = "http://" + location.host + "/rest/app/enroll/lottery2?aid=" + $scope.aid;
    http2.get('/rest/mp/app/enroll/lotteryRounds?aid=' + $scope.aid, function (rsp) {
        $scope.rounds = rsp.data;
    });
    getWinners();
}]);
xxtApp.directive('tmsDatetime', ['$timeout', function ($timeout) {
    return {
        restrict: 'A',
        scope: { value: '=' },
        template: "<div><span ng-show='value<>0' ng-bind=\"value*1000|date:'yyyy-MM-dd HH:mm'\"></span></div>",
        controller: function () { },
        replace: true
    };
}]);