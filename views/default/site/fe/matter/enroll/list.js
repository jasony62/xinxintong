'use strict';
require('./list.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['$http', '$q', 'ls', function($http, $q, LS) {
    var Round, _ins;
    Round = function() {};
    Round.prototype.list = function() {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('round/list', 'site', 'app');
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
ngApp.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
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
ngApp.factory('Record', ['$http', '$q', 'ls', function($http, $q, LS) {
    var Record, _ins;
    Record = function() {};
    Record.prototype.list = function(owner, rid) {
        var deferred = $q.defer(),
            url;
        url = LS.j('record/list', 'site', 'app');
        url += '&owner=' + owner;
        rid && rid.length && (url += '&rid=' + rid);
        $http.get(url).success(function(rsp) {
            var records, record, i, l;
            if (rsp.err_code == 0) {
                records = rsp.data.records;
                if (records && records.length) {
                    for (i = 0, l = records.length; i < l; i++) {
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
        ins: function() {
            if (_ins) {
                return _ins;
            }
            _ins = new Record();
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecords', ['$scope', 'Record', 'ls', '$sce', function($scope, Record, LS, $sce) {
    var facRecord, options, fnFetch,
        oApp = $scope.app;

    $scope.value2Label = function(record, schemaId) {
        var val, i, j, s, aVal, aLab = [];
        if (oApp._schemasById && record.data) {
            val = record.data[schemaId];
            if (val === undefined) return '';
            s = oApp._schemasById[schemaId];
            if (s && s.ops && s.ops.length) {
                aVal = val.split(',');
                for (i = 0, j = s.ops.length; i < j; i++) {
                    aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
                }
                if (aLab.length) val = aLab.join(',');
            }

        } else {
            val = '';
        }
        return $sce.trustAsHtml(val);
    };
    $scope.score2Html = function(record, schemaId) {
        var label = '',
            schema = oApp._schemasById[schemaId],
            val;

        if (schema && record.data) {
            val = record.data[schemaId];
            if (schema.ops && schema.ops.length) {
                schema.ops.forEach(function(op, index) {
                    label += '<div>' + op.l + ': ' + (val[op.v] ? val[op.v] : 0) + '</div>';
                });
            }
        }
        return $sce.trustAsHtml(label);
    };
    facRecord = Record.ins();
    options = {
        owner: 'U',
        rid: LS.p.rid
    };
    fnFetch = function() {
        facRecord.list(options.owner, options.rid).then(function(records) {
            $scope.records = records;
        });
    };
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        if (options.rid !== data[0].rid) {
            options.rid = data[0].rid;
            fnFetch();
        }
    });
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        if (options.owner !== data[0].id) {
            options.owner = data[0].id;
            fnFetch();
        }
    });
    $scope.$watch('options', function(nv) {
        $scope.fetch();
    }, true);
    $scope.options = options;
    $scope.fetch = fnFetch;
}]);
ngApp.controller('ctrlList', ['$scope', function($scope) {
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.owner', data);
        }
    });
}]);
