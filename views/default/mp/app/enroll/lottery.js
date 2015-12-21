xxtApp.register.controller('lotteryCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'lottery';
    var getWinners = function() {
        var url = '/rest/mp/app/enroll/lottery/winnersGet?aid=' + $scope.aid;
        if ($scope.editingRound)
            url += '&rid=' + $scope.editingRound.round_id;
        http2.get(url, function(rsp) {
            $scope.winners = rsp.data;
        });
    };
    $scope.aTargets = null;
    $scope.gotoCode = function() {
        var app, url;
        app = $scope.$parent.editing;
        if (app.lottery_page_id != 0) {
            window.open('/rest/code?pid=' + app.lottery_page_id, '_self');
        } else {
            var url;
            url = '/rest/mp/app/enroll/lottery/pageCreate?aid=' + app.id;
            http2.get(url, function(rsp) {
                app.lottery_page_id = rsp.data;
                window.open('/rest/code?pid=' + app.lottery_page_id, '_self');
            });
        }
    };
    $scope.resetCode = function() {
        var app, url;
        if (window.confirm('重置操作将丢失已做修改，确定？')) {
            app = $scope.$parent.editing;
            url = '/rest/mp/app/enroll/lottery/pageReset?aid=' + app.id;
            http2.get(url, function(rsp) {
                window.open('/rest/code?pid=' + app.lottery_page_id, '_self');
            });
        }
    };
    $scope.addRound = function() {
        http2.post('/rest/mp/app/enroll/lottery/roundAdd?aid=' + $scope.aid, null, function(rsp) {
            $scope.rounds.push(rsp.data);
        });
    };
    $scope.open = function(round) {
        $scope.editingRound = round;
        $scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
        getWinners();
    };
    $scope.updateRound = function(name) {
        var nv = {};
        nv[name] = $scope.editingRound[name];
        http2.post('/rest/mp/app/enroll/lottery/roundUpdate?aid=' + $scope.aid + '&rid=' + $scope.editingRound.round_id, nv, function(rsp) {});
    };
    $scope.removeRound = function() {
        http2.post('/rest/mp/app/enroll/lottery/roundRemove?aid=' + $scope.aid + '&rid=' + $scope.editingRound.round_id, null, function(rsp) {
            var i = $scope.rounds.indexOf($scope.editingRound);
            $scope.rounds.splice(i, 1);
        });
    };
    $scope.addTarget = function() {
        var target = {
            tags: []
        };
        $scope.aTargets.push(target);
    };
    $scope.removeTarget = function(i) {
        $scope.aTargets.splice(i, 1);
    };
    $scope.saveTargets = function() {
        var arr = [];
        for (var i in $scope.aTargets)
            arr.push({
                tags: $scope.aTargets[i].tags
            });
        $scope.editingRound.targets = JSON.stringify(arr);
        $scope.updateRound('targets');
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected, state) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.aTargets[state].tags) {
                if (aSelected[i] === $scope.aTargets[state].tags[j]) {
                    existing = true;
                    break;
                }
            }!existing && aNewTags.push(aSelected[i]);
        }
        $scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag, state) {
        $scope.aTargets[state].tags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
            $scope.update('tags');
        }
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed, state) {
        $scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
    });
    $scope.$parent.$watch('editing', function(nv) {
        if (!nv) return;
        $scope.aTags = $scope.editing.tags;
        $scope.lotteryUrl = "http://" + location.host + "/rest/op/enroll/lottery?aid=" + $scope.aid;
        http2.get('/rest/mp/app/enroll/lottery/roundsGet?aid=' + $scope.aid, function(rsp) {
            $scope.rounds = rsp.data;
        });
        getWinners();
    });
}]);