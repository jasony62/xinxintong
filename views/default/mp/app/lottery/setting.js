(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'setting';
        $scope.years = [2014, 2015, 2016];
        $scope.months = [];
        $scope.days = [];
        $scope.hours = [];
        $scope.minutes = [];
        for (var i = 1; i <= 12; i++)
            $scope.months.push(i);
        for (var i = 1; i <= 31; i++)
            $scope.days.push(i);
        for (var i = 0; i <= 23; i++)
            $scope.hours.push(i);
        for (var i = 0; i <= 59; i++)
            $scope.minutes.push(i);
        $scope.updateTime = function(name) {
            var time, p;
            time = $scope[name].getTime();
            p = {};
            p[name] = time / 1000;
            http2.post('/rest/mp/app/lottery/update?lottery=' + $scope.lid, p);
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.lottery.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function() {
            $scope.lottery.pic = '';
            $scope.update('pic');
        };
        $scope.$watch('lottery', function(lottery) {
            if (!lottery) return;
            var date;
            if (lottery.start_at == 0) {
                date = new Date();
                date.setTime(date.getTime());
            } else
                date = new Date(lottery.start_at * 1000);
            $scope.start_at = (function(date) {
                return {
                    year: date.getFullYear(),
                    month: date.getMonth() + 1,
                    mday: date.getDate(),
                    hour: date.getHours(),
                    minute: date.getMinutes(),
                    getTime: function() {
                        var d = new Date(this.year, this.month - 1, this.mday, this.hour, this.minute, 0, 0);
                        return d.getTime();
                    }
                };
            })(date);
            if (lottery.end_at == 0) {
                date = new Date();
                date.setTime(date.getTime() + 86400000);
            } else
                date = new Date(lottery.end_at * 1000);
            $scope.end_at = (function(date) {
                return {
                    year: date.getFullYear(),
                    month: date.getMonth() + 1,
                    mday: date.getDate(),
                    hour: date.getHours(),
                    minute: date.getMinutes(),
                    getTime: function() {
                        var d = new Date(this.year, this.month - 1, this.mday, this.hour, this.minute, 0, 0);
                        return d.getTime();
                    }
                };
            })(date);
        });
    }]);
})();