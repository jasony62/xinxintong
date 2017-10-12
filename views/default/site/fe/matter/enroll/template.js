'use strict';
require('./main.css');
require('./directive.css');
require('./template.css');

require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.coinpay.js');

require('./directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'page.ui.xxt', 'directive.enroll', 'siteuser.ui.xxt', 'favor.ui.xxt']);
ngApp.provider('ls', function() {
    var _baseUrl = '/rest/site/fe/matter/enroll',
        _params = {};

    this.params = function(params) {
        var ls;
        ls = location.search;
        angular.forEach(params, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            _params[q] = match ? match[1] : '';
        });
        return _params;
    };

    this.$get = function() {
        return {
            p: _params,
            j: function(method) {
                var i = 1,
                    l = arguments.length,
                    url = _baseUrl,
                    _this = this,
                    search = [];
                method && method.length && (url += '/' + method);
                for (; i < l; i++) {
                    search.push(arguments[i] + '=' + _params[arguments[i]]);
                };
                search.length && (url += '?' + search.join('&'));
                return url;
            }
        };
    };
});
ngApp.config(['$controllerProvider', 'lsProvider', function($cp, lsProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    lsProvider.params(['site', 'app', 'rid', 'page', 'ek', 'preview', 'newRecord', 'ignoretime']);
}]);
ngApp.factory('Round', ['$http', '$q', function($http, $q) {
    var Round = function(scenario, template) {
        this.scenario = scenario;
        this.template = template;
    };
    Round.prototype.list = function() {
        var _this, deferred, promise, url;
        _this = this;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/site/fe/matter/enroll/template/round/list';
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
var LS = (function() {
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
        var i, j, l, url = '/rest/site/fe/matter/enroll/template',
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
ngApp.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
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
ngApp.factory('Record', ['$http', '$q', function($http, $q) {
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
        url = '/rest/site/fe/matter/enroll/template/record/get';
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
        url = '/rest/site/fe/matter/enroll/template/record/list';
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
ngApp.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
    var facRecord = Record.ins(LS.p.scenario, LS.p.template),
        schemas = [],
        i;
    facRecord.get($scope.CustomConfig);
    $scope.Record = facRecord;
    for (i in $scope.page.data_schemas) {
        schemas.push($scope.page.data_schemas[i].schema);
    }
    $scope.value2Label = function(key) {
        var val, i, j, s, aVal, aLab = [];
        if (schemas && facRecord.current.data) {
            val = facRecord.current.data[key];
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
        } else {
            return '';
        }
    };
}]);
ngApp.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
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
ngApp.controller('ctrlOwnerOptions', ['$scope', function($scope) {
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
ngApp.controller('ctrlOrderbyOptions', ['$scope', function($scope) {
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
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    function renew(page, config) {
        $scope.CustomConfig = config;
        $http.post(LS.j('pageGet', 'scenario', 'template') + '&page=' + page, config).success(function(rsp) {
            var params;
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            params = rsp.data;
            $scope.params = params;
            $scope.page = params.page;
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
    };
    window.renew = function(page, config) {
        var phase;
        phase = $scope.$root.$$phase;
        if (phase === '$digest' || phase === '$apply') {
            renew(page, config);
        } else {
            $scope.$apply(function() {
                renew(page, config);
            });
        }
    };
    $scope.errmsg = '';
    $scope.data = {
        member: {}
    };
    $scope.CustomConfig = {};
    $scope.gotoPage = function(event, page, ek, rid, newRecord) {};
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

    window.renew(LS.p.page, {});
}]);