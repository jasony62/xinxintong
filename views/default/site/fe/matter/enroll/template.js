'use strict';
require('./main.css');
require('./directive.css');
require('./template.css');

require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');

require('./directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'http.ui.xxt', 'page.ui.xxt', 'directive.enroll']);
ngApp.config(['$controllerProvider', '$locationProvider', function($cp, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $locationProvider.html5Mode(true);
}]);
ngApp.factory('Round', ['tmsLocation', 'http2', function(LS, http2) {
    var Round = function() {};
    Round.prototype.list = function() {
        return http2.get(LS.s('round/list', 'scenario', 'template'));
    };
    return Round;
}]);
ngApp.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
    var facRound, onDataReadyCallbacks;
    facRound = new Round();
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
ngApp.factory('Record', ['tmsLocation', 'http2', '$q', function(LS, http2, $q) {
    var _ins, _running, Record;
    Record = function() {
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
        url = LS.j('record/get', 'scenario', 'template');
        http2.post(url, config).then(function(rsp) {
            var record;
            record = rsp.data;
            _this.current = record;
            deferred.resolve(record);
            _running = false;
        });
        return deferred.promise;
    };
    Record.prototype.list = function(owner, rid) {
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = LS.j('record/list', 'scenario', 'template');
        rid !== undefined && rid.length && (url += '&rid=' + rid);
        owner !== undefined && owner.length && (url += '&owner=' + owner);
        http2.get(url).then(function(rsp) {
            var records, record;
            records = rsp.data.records;
            if (records && records.length) {
                for (var i = 0; i < records.length; i++) {
                    record = records[i];
                    record.data.member && (record.data.member = JSON.parse(record.data.member));
                }
            }
            deferred.resolve(records);
        });
        return deferred.promise;
    };
    return {
        ins: function() {
            if (_ins) {
                return _ins;
            }
            _ins = new Record();
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecord', ['$scope', 'tmsLocation', 'Record', function($scope, LS, Record) {
    var facRecord = Record.ins(),
        schemas = [];
    facRecord.get($scope.CustomConfig);
    $scope.Record = facRecord;
    $scope.page.data_schemas.forEach(function(oSchemaWrap) {
        schemas.push(oSchemaWrap.schema);
    });
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
ngApp.controller('ctrlRecords', ['$scope', 'tmsLocation', 'Record', function($scope, LS, Record) {
    var facRecord, options, fnFetch, rid;
    if (LS.s().rid === '' && $scope.$parent.ActiveRound) {
        rid = $scope.$parent.ActiveRound.rid;
    } else {
        rid = LS.s().rid;
    }
    facRecord = Record.ins(LS.s().scenario, LS.s().template);
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
            label: '留言'
        }
    };
}]);
ngApp.controller('ctrlMain', ['$scope', 'tmsLocation', 'http2', '$timeout', '$q', function($scope, LS, http2, $timeout, $q) {
    function renew(page, config) {
        $scope.CustomConfig = config;
        http2.post(LS.j('pageGet', 'scenario', 'template') + '&page=' + page, config).then(function(rsp) {
            var params;
            params = rsp.data;
            $scope.params = params;
            $scope.page = params.page;
            $scope.User = params.user;
            $scope.ActiveRound = params.app.appRound;
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
    window.renew(LS.s().page, {});
}]);