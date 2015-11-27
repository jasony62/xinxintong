app = angular.module('app', ['ngSanitize']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.filter("maskmobile", function() {
    return function(mobile) {
        if (mobile && mobile.length > 4) {
            var i, start = Math.round((mobile.length - 4) / 2);
            mobile = mobile.split('');
            for (i = 0; i < 4; i++)
                mobile[start + i] = '*';
            return mobile.join('');
        } else
            return '****';
    }
});
app.directive('dynamicHtml', function($compile) {
    return {
        restrict: 'EA',
        replace: true,
        link: function(scope, ele, attrs) {
            scope.$watch(attrs.dynamicHtml, function(html) {
                if (html && html.length) {
                    ele.html(html);
                    $compile(ele.contents())(scope);
                }
            });
        }
    };
});
var LS = (function(fields) {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(fields, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var j, l, url = '/rest/app/enroll',
            _this = this,
            search = [];
        method && method.length && (url += '/' + method);
        if (arguments.length > 1) {
            for (i = 1, l = arguments.length; i < l; i++) {
                search.push(arguments[i] + '=' + _this.p[arguments[i]]);
            };
            url += '?' + search.join('&');
        }
        return url;
    };
    return {
        p: locationSearch(),
        j: j
    };
})(['aid', 'rid']);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$interval', function($scope, $http, $timeout, $interval) {
    var mySwiper, timer, winnerIndex = -1;
    $scope.speed = 50;
    $scope.times = 0;
    $scope.stopping = false;
    $scope.winners = [];
    $scope.currentRound = null;
    $scope.start = function() {
        if (winnerIndex !== -1) {
            $scope.persons.splice(winnerIndex - 1, 1);
            mySwiper.removeSlide(winnerIndex);
            mySwiper.updateSlidesSize();
            winnerIndex = -1;
        }
        $scope.running = true;
        timer = $interval(function() {
            mySwiper.slideNext();
        }, $scope.speed);
        if ($scope.currentRound.autoplay === 'Y')
            $timeout(function() {
                $scope.stop()
            }, 1000);
    };
    $scope.stop = function() {
        var getWinner = function() {
            var winner;
            winnerIndex = mySwiper.activeIndex;
            winnerIndex > $scope.persons.length && (winnerIndex = 1);
            winner = $scope.persons[winnerIndex - 1];
            if (winner) {
                $scope.winners.push(winner);
                $http.post('/rest/op/enroll/lottery/done?aid=' + LS.p.aid + '&rid=' + LS.p.rid + '&ek=' + winner.enroll_key, {
                    openid: winner.openid
                });
            }
            $scope.stopping = false;
            $scope.running = false;
            $scope.times++;
            if ($scope.currentRound.autoplay === 'Y' && $scope.times < $scope.currentRound.times)
                $scope.start();
        };
        $scope.stopping = true;
        $interval.cancel(timer);
        var timer2, i = 0,
            steps = Math.round(Math.random() * 10);
        timer2 = $interval(function() {
            mySwiper.slideNext();
            if (i === steps) {
                $interval.cancel(timer2);
                if ($scope.currentRound.aTargets && $scope.currentRound.aTargets.length > 0) {
                    var target = $scope.currentRound.aTargets[$scope.times % $scope.currentRound.aTargets.length];
                    if (target.tags && target.tags.length > 0) {
                        var candidate;
                        candidate = $scope.persons[mySwiper.activeIndex];
                        if (candidate && target.tags.indexOf(candidate.tags) === -1) {
                            var j = [],
                                timer3;
                            timer3 = $interval(function() {
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
    $scope.getPersons = function() {
        $http.get('/rest/op/enroll/lottery/playersGet?aid=' + LS.p.aid + '&rid=' + LS.p.rid + '&hasData=N').
        success(function(rsp) {
            $scope.persons = rsp.data[0];
            $scope.winners = rsp.data[1];
            $timeout(function() {
                $scope.stopping = false;
                $scope.running = true;
                mySwiper = new Swiper('.swiper-container', {
                    slidesPerView: 1,
                    mode: 'horizontal',
                    loop: true,
                    speed: $scope.speed
                });
                timer = $interval(function() {
                    mySwiper.slideNext();
                }, $scope.speed);
                if ($scope.currentRound.autoplay === 'Y')
                    $timeout(function() {
                        $scope.stop()
                    }, 1000);
            });
        });
    };
    $scope.empty = function() {
        $http.get('/rest/op/enroll/lottery/empty?aid=' + LS.p.aid + '&rid=' + LS.p.rid).
        success(function(rsp) {
            location.reload();
        });
    };
    $http.get('/rest/op/enroll/lottery/pageGet?aid=' + LS.p.aid).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.Page = params.page;
        (function setPage(page) {
            if (page.ext_css && page.ext_css.length) {
                angular.forEach(page.ext_css, function(css) {
                    var link, head;
                    link = document.createElement('link');
                    link.href = css.url;
                    link.rel = 'stylesheet';
                    head = document.querySelector('head');
                    head.appendChild(link);
                });
            }
            if (page.ext_js && page.ext_js.length) {
                angular.forEach(page.ext_js, function(js) {
                    $.getScript(js.url);
                });
            }
            if (page.js && page.js.length) {
                (function dynamicjs() {
                    eval(page.js);
                })();
            }
        })(params.page);

    });
}]);
app.controller('ctrlRounds', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    $scope.round = '';
    $scope.shiftRound = function() {
        var url = '/rest/op/enroll/lottery?aid=' + LS.p.aid + '&_=' + (new Date()).getTime();
        if ($scope.round && $scope.round.length > 0) url += '&rid=' + $scope.round;
        location.href = url;
    };
    $http.get('/rest/op/enroll/lottery/roundsGet?aid=' + LS.p.aid).success(function(rsp) {
        $scope.rounds = rsp.data;
        for (var i in $scope.rounds) {
            if (LS.p.rid === $scope.rounds[i].round_id) {
                $scope.round = $scope.rounds[i].round_id;
                $scope.$parent.currentRound = $scope.rounds[i];
                $scope.$parent.currentRound.aTargets = eval($scope.$parent.currentRound.targets);
                $timeout(function() {
                    $scope.$parent.getPersons();
                });
                break;
            }
        }
    });
}]);