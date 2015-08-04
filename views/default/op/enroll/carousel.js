angular.module('carouselApp', []).filter("maskmobile", function () {
    return function (mobile) {
        if (mobile && mobile.length > 4) {
            var i, start = Math.round((mobile.length - 4) / 2);
            mobile = mobile.split('');
            for (i = 0; i < 4; i++)
                mobile[start + i] = '*';
            return mobile.join('');
        } else
            return '****';
    }
}).controller('ctrl', ['$scope', '$http', '$timeout', '$interval', function ($scope, $http, $timeout, $interval) {
    var mySwiper, timer, winnerIndex = -1;
    $scope.speed = 50;
    $scope.times = 0;
    $scope.stopping = false;
    $scope.winners = [];
    $scope.start = function () {
        if (winnerIndex !== -1) {
            $scope.persons.splice(winnerIndex - 1, 1);
            mySwiper.removeSlide(winnerIndex);
            mySwiper.updateSlidesSize();
            winnerIndex = -1;
        }
        $scope.running = true;
        timer = $interval(function () { mySwiper.slideNext(); }, $scope.speed);
        if ($scope.currentRound.autoplay === 'Y')
            $timeout(function () { $scope.stop() }, 1000);
    };
    $scope.stop = function () {
        var getWinner = function () {
            var winner;
            winnerIndex = mySwiper.activeIndex;
            winnerIndex > $scope.persons.length && (winnerIndex = 1);
            winner = $scope.persons[winnerIndex - 1];
            if (winner) {
                $scope.winners.push(winner);
                $http.post('/rest/op/enroll/lottery/done?aid=' + $scope.aid + '&rid=' + $scope.round + '&ek=' + winner.enroll_key, { openid: winner.openid });
            }
            $scope.stopping = false;
            $scope.running = false;
            $scope.times++;
            if ($scope.currentRound.autoplay === 'Y' && $scope.times < $scope.currentRound.times)
                $scope.start();
        };
        $scope.stopping = true;
        $interval.cancel(timer);
        var timer2, i = 0, steps = Math.round(Math.random() * 10);
        timer2 = $interval(function () {
            mySwiper.slideNext();
            if (i === steps) {
                $interval.cancel(timer2);
                if ($scope.currentRound.aTargets && $scope.currentRound.aTargets.length > 0) {
                    var target = $scope.currentRound.aTargets[$scope.times % $scope.currentRound.aTargets.length];
                    if (target.tags && target.tags.length > 0) {
                        var candidate;
                        candidate = $scope.persons[mySwiper.activeIndex];
                        if (candidate && target.tags.indexOf(candidate.tags) === -1) {
                            var j = [], timer3;
                            timer3 = $interval(function () {
                                candidate = $scope.persons[mySwiper.activeIndex];
                                if (candidate && target.tags.indexOf(candidate.tags) !== -1 || j.length === $scope.persons.length) {
                                    $interval.cancel(timer3);
                                    getWinner();
                                } else {
                                    mySwiper.slideNext();
                                    if (j.indexOf(mySwiper.activeIndex) === -1)
                                        j.push(mySwiper.activeIndex);
                                }
                            }, $scope.speed);
                        } else {
                            getWinner();
                        }
                    } else {
                        getWinner();
                    }
                } else {
                    getWinner();
                }
            }
            i++;
        }, $scope.speed);
    };
    $scope.getPersons = function () {
        $http.get('/rest/op/enroll/lottery/playersGet?aid=' + $scope.aid + '&rid=' + $scope.round).
            success(function (rsp) {
                $scope.persons = rsp.data[0];
                $scope.winners = rsp.data[1];
                $timeout(function () {
                    $scope.stopping = false;
                    $scope.running = true;
                    mySwiper = new Swiper('.swiper-container', {
                        slidesPerView: 1,
                        mode: 'horizontal',
                        loop: true,
                        speed: $scope.speed
                    });
                    timer = $interval(function () { mySwiper.slideNext(); }, $scope.speed);
                    if ($scope.currentRound.autoplay === 'Y')
                        $timeout(function () { $scope.stop() }, 1000);
                });
            });
    };
    $scope.shiftRound = function () {
        var url = '/rest/op/enroll/lottery?aid=' + $scope.aid + '&_=' + (new Date()).getTime();
        if ($scope.round && $scope.round.length > 0) url += '&rid=' + $scope.round;
        location.href = url;
    };
    $scope.empty = function () {
        $http.get('/rest/op/enroll/lottery/empty?aid=' + $scope.aid + '&rid=' + $scope.round).
            success(function (rsp) {
                $scope.shiftRound();
            });
    };
    $scope.$watch('aid', function (nv) {
        $http.get('/rest/op/enroll/lottery/roundsGet?aid=' + $scope.aid).
            success(function (rsp) {
                $scope.rounds = rsp.data;
                for (var i in $scope.rounds) {
                    if ($scope.round === $scope.rounds[i].round_id) {
                        $scope.currentRound = $scope.rounds[i];
                        $scope.currentRound.aTargets = eval($scope.currentRound.targets);
                        break;
                    }
                }
            });
    });
    $scope.$watch('round', function (nv) {
        if (nv && nv.length)
            $scope.getPersons();
    });
}]);
