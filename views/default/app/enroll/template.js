app = angular.module('app', ['ngSanitize', 'infinite-scroll']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.factory('Round', ['$http', '$q', function($http, $q) {
    var Round = function(scenario, template) {
        this.scenario = scenario;
        this.template = template;
    };
    Round.prototype.list = function() {
        var _this, deferred, promise, url;
        _this = this;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/enroll/template/round/list';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Round;
}]);
LS = (function() {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(['scenario', 'template', 'page', 'rid'], function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var j, l, url = '/rest/app/enroll/template',
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
})();
app.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
    var facRound, onDataReadyCallbacks;
    facRound = new Round(LS.p.scenario, LS.p.template);
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
    var _ins, _running, Record;
    Record = function(scenario, template) {
        this.scenario = scenario;
        this.template = template;
        this.current = {
            data: {},
            enroll_at: 0
        };
    };
    _running = false;
    Record.prototype.get = function(config) {
        if (_running) return false;
        _running = true;
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = '/rest/app/enroll/template/record/get';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        $http.post(url, config).success(function(rsp) {
            var record;
            record = rsp.data;
            if (rsp.err_code == 0) {
                _this.current = record;
                deferred.resolve(record);
            }
            _running = false;
        });
        return deferred.promise;
    };
    Record.prototype.list = function(owner, rid) {
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = '/rest/app/enroll/template/record/list';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        rid !== undefined && rid.length && (url += '&rid=' + rid);
        owner !== undefined && owner.length && (url += '&owner=' + owner);
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
                deferred.resolve(records);
            }
        });
        return deferred.promise;
    };
    return {
        ins: function(scenario, template) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(scenario, template);
            return _ins;
        }
    };
}]);
app.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
    var facRecord;
    facRecord = Record.ins(LS.p.scenario, LS.p.template);
    facRecord.get($scope.CustomConfig);
    $scope.Record = facRecord;
}]);
app.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
    var facRecord, options, fnFetch, rid;
    if (LS.p.rid === '' && $scope.$parent.ActiveRound) {
        rid = $scope.$parent.ActiveRound.rid;
    } else {
        rid = LS.p.rid;
    }
    facRecord = Record.ins(LS.p.scenario, LS.p.template);
    options = {
        owner: 'A',
        rid: rid
    };
    fnFetch = function() {
        facRecord.list(options.owner, options.rid).then(function(records) {
            $scope.records = records;
        });
    };
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        options.rid = data[0].rid;
        fnFetch();
    });
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        options.owner = data[0].id;
        fnFetch();
    });
    $scope.fetch = fnFetch;
    $scope.options = options;
}]);
app.controller('ctrlOwnerOptions', ['$scope', function($scope) {
    $scope.owners = {
        'A': {
            id: 'A',
            label: '全部'
        },
        'U': {
            id: 'U',
            label: '我的'
        }
    };
    $scope.match = function(owner) {
        return $scope.owners[owner.id];
    }
}]);
app.controller('ctrlOrderbyOptions', ['$scope', function($scope) {
    $scope.orderbys = {
        time: {
            id: 'time',
            label: '最新'
        },
        score: {
            id: 'score',
            label: '点赞'
        },
        remark: {
            id: 'remark',
            label: '评论'
        }
    };
}]);
app.controller('ctrlStatistic', ['$scope', '$http', function($scope, $http) {
    var fnFetch;
    fnFetch = function() {
        $http.post(LS.j('statGet', 'scenario', 'template'), $scope.CustomConfig).success(function(rsp) {
            $scope.statistic = rsp.data;
        });
    };
    fnFetch();
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    window.renew = function(page, config) {
        $scope.$apply(function() {
            $scope.CustomConfig = config;
            $http.post(LS.j('pageGet', 'scenario', 'template') + '&page=' + page, config).success(function(rsp) {
                var params;
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                params = rsp.data;
                $scope.params = params;
                $scope.Page = params.page;
                $scope.User = params.user;
                $scope.ActiveRound = params.activeRound;
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
        });
    };
    $scope.errmsg = '';
    $scope.data = {
        member: {}
    };
    $scope.CustomConfig = {};
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {};
    $scope.addRecord = function(event) {};
    $scope.editRecord = function(event, page) {};
    $scope.likeRecord = function(event) {};
    $scope.remarkRecord = function(event) {};
    $scope.openMatter = function(id, type) {};
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.rounds', data);
        }
    });
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.owner', data);
        }
    });
}]);
app.factory('Schema', ['$http', '$q', function($http, $q) {
    var schema, Schema;
    schema = null;
    Schema = function() {};
    Schema.prototype.get = function() {
        var deferred;
        deferred = $q.defer();
        if (schema !== null)
            deferred.resolve(schema);
        else {
            $http.get(LS.j('page/schemaGet', 'scenario', 'template')).success(function(rsp) {
                schema = rsp.data;
                deferred.resolve(schema);
            });
        }
        return deferred.promise;
    };
    return Schema;
}]);
app.filter('value2Label', ['Schema', function(Schema) {
    var schemas;
    (new Schema()).get().then(function(data) {
        schemas = data;
    });
    return function(val, key) {
        var i, j, s, aVal, aLab = [];
        if (val === undefined) return '';
        for (i = 0, j = schemas.length; i < j; i++) {
            s = schemas[i];
            if (schemas[i].id === key) {
                s = schemas[i];
                break;
            }
        }
        if (s && s.ops && s.ops.length) {
            aVal = val.split(',');
            for (i = 0, j = s.ops.length; i < j; i++) {
                aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
            }
            if (aLab.length) return aLab.join(',');
        }
        return val;
    };
}]);