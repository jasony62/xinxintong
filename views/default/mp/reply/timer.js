xxtApp.controller('timerCtrl', ['$scope', 'http2', 'matterTypes', function($scope, http2, matterTypes) {
    $scope.matterTypes = matterTypes;
    $scope.mdays = ['忽略'];
    while ($scope.mdays.length < 32) {
        $scope.mdays.push('' + $scope.mdays.length);
    }
    $scope.create = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
            if (aSelected.length === 1) {
                aSelected[0].type = matterType;
                http2.post('/rest/mp/call/timer/create', aSelected[0], function(rsp) {
                    var timer;
                    timer = rsp.data;
                    timer.schedule = getSchedule(timer);
                    $scope.timers.splice(0, 0, timer);
                    $scope.edit(timer);
                });
            }
        });
    };
    $scope.remove = function(index) {
        http2.get('/rest/mp/call/timer/delete?id=' + $scope.editing.id, function(rsp) {
            $scope.timers.splice(index, 1);
            if ($scope.timers.length === 0)
                $scope.editing = null;
            else if (index === $scope.timers.length)
                $scope.edit($scope.timers[--index]);
            else
                $scope.edit($scope.timers[index]);
        });
    };
    $scope.edit = function(timer) {
        $scope.editing = timer;
        $scope.logs = null;
        http2.get('/rest/mp/call/timer/logGet?taskid=' + timer.id, function(rsp) {
            $scope.logs = rsp.data;
        });
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.editing[name];
        http2.post('/rest/mp/call/timer/update?id=' + $scope.editing.id, p, function() {
            $scope.editing.schedule = getSchedule($scope.editing);
        });
    };
    $scope.setReply = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
            if (aSelected.length === 1) {
                var p = {
                    rt: matterType,
                    rid: aSelected[0].id
                };
                http2.post('/rest/mp/call/timer/setreply?id=' + $scope.editing.id, p, function(rsp) {
                    $scope.editing.matter = aSelected[0];
                });
            }
        });
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = rsp.data.parent_mpid && rsp.data.parent_mpid.length;
    });
    var getSchedule = function(timer) {
        var schedule = [];
        //timer.min == -1 && (timer.min = '*');
        //schedule.push(timer.min);
        timer.hour == -1 && (timer.hour = '忽略');
        schedule.push(timer.hour);
        timer.mday == -1 && (timer.mday = '忽略');
        schedule.push(timer.mday);
        //timer.mon == -1 && (timer.mon = '*');
        //schedule.push(timer.mon);
        timer.wday == -1 && (timer.wday = '忽略');
        schedule.push(timer.wday);
        return schedule.join(',');
    };
    http2.get('/rest/mp/call/timer/get', function(rsp) {
        var i, j, timer;
        $scope.timers = rsp.data;
        for (i = 0, j = $scope.timers.length; i < j; i++) {
            timer = $scope.timers[i];
            timer.schedule = getSchedule(timer);
        }
        j > 0 && $scope.edit(timer);
    });
}]);