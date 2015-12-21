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
        var j, l, url = '/rest/op/enroll',
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
})(['mpid', 'aid', 'page']);
var setPage = function($scope, page) {
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
        var i, l, loadJs;
        i = 0;
        l = page.ext_js.length;
        loadJs = function() {
            var js;
            js = page.ext_js[i];
            $.getScript(js.url, function() {
                i++;
                if (i === l) {
                    if (page.js && page.js.length) {
                        $scope.$apply(
                            function dynamicjs() {
                                eval(page.js);
                                $scope.Page = page;
                            }
                        );
                    }
                } else {
                    loadJs();
                }
            });
        };
        loadJs();
    } else if (page.js && page.js.length) {
        (function dynamicjs() {
            eval(page.js);
            $scope.Page = page;
        })();
    } else {
        $scope.Page = page;
    }
};
app = angular.module('app', ['ngSanitize', 'infinite-scroll']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
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
app.factory('Round', ['$http', '$q', function($http, $q) {
    var Round, _ins;
    Round = function() {};
    Round.prototype.list = function() {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('round/list', 'mpid', 'aid');
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function() {
            _ins = _ins ? _ins : new Round();
            return _ins;
        }
    };
}]);
app.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
    var facRound, onDataReadyCallbacks;
    facRound = Round.ins();
    facRound.list().then(function(rounds) {
        $scope.rounds = rounds;
        angular.forEach(onDataReadyCallbacks, function(cb) {
            cb(rounds);
        });
    });
    onDataReadyCallbacks = [];
    $scope.onDataReady = function(callback) {
        onDataReadyCallbacks.push(callback);
    };
    $scope.match = function(matched) {
        var i, l, round;
        for (i = 0, l = $scope.rounds.length; i < l; i++) {
            round = $scope.rounds[i];
            if (matched.rid === $scope.rounds[i].rid) {
                return $scope.rounds[i];
            }
        }
        return false;
    };
}]);
app.factory('Record', ['$http', '$q', function($http, $q) {
    var Record, _ins, _running;
    Record = function() {};
    Record.prototype.list = function(options) {
        var url, deferred;
        deferred = $q.defer();
        url = LS.j('record/list', 'mpid', 'aid');
        options.rid !== undefined && options.rid.length && (url += '&rid=' + options.rid);
        options.enroller !== undefined && options.enroller.length && (url += '&enroller=' + options.enroller);
        $http.get(url).success(function(rsp) {
            var records, record;
            if (rsp.err_code == 0) {
                records = rsp.data.records;
                if (records && records.length) {
                    for (var i = 0; i < records.length; i++) {
                        record = records[i];
                        record.data.member && (record.data.member = JSON.parse(record.data.member));
                    }
                }
                deferred.resolve(rsp.data);
            }
        });
        return deferred.promise;
    };
    return {
        ins: function() {
            !_ins && (_ins = new Record());
            return _ins;
        }
    };
}]);
app.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
    var facRecord, options, fnFetch;
    facRecord = Record.ins();
    options = {
        rid: LS.p.rid,
        enroller: ''
    };
    fnFetch = function() {
        facRecord.list(options).then(function(data) {
            $scope.records = data.records;
        });
    };
    $scope.$on('xxt.app.enroll.filter.enrollers', function(event, data) {
        if (options.enroller !== data[0].openid) {
            options.enroller = data[0].openid;
            fnFetch();
        }
    });
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        if (options.rid !== data[0].rid) {
            options.rid = data[0].rid;
            fnFetch();
        }
    });
    $scope.$watch('options', function(nv) {
        fnFetch();
    }, true);
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var tasksOfOnReady = [];
    $scope.errmsg = '';
    $scope.onReady = function(task) {
        if ($scope.params) {
            PG.exec(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    $http.get(LS.j('pageGet', 'mpid', 'aid', 'page')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.ActiveRound = params.activeRound;
        setPage($scope, params.page);
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, PG.exec);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
    });
}]);