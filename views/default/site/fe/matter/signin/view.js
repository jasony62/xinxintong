define(["require", "angular", "angular-sanitize", "xxt-share", "enroll-directive", "enroll-common"], function(require, angular) {
    'use strict';
    ngApp.factory('Round', ['$http', '$q', function($http, $q) {
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
    ngApp.factory('Record', ['$http', '$q', function($http, $q) {
        var Record, _ins, _running;
        Record = function() {
            this.current = {
                enroll_at: 0
            };
        };
        _running = false;
        Record.prototype.get = function(ek) {
            if (_running) return false;
            _running = true;
            var _this, url, deferred;
            _this = this;
            deferred = $q.defer();
            url = LS.j('record/get', 'site', 'app');
            ek && (url += '&ek=' + ek);
            $http.get(url).success(function(rsp) {
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
            var url, deferred;
            deferred = $q.defer();
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
            ins: function(siteId, appId, rid, $scope) {
                if (_ins) {
                    return _ins;
                }
                _ins = new Record(siteId, appId, rid, $scope);
                return _ins;
            }
        };
    }]);
    ngApp.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
        var facRecord, options, fnFetch, schemas;
        schemas = JSON.parse($scope.Page.data_schemas);
        schemas = schemas.list.schemas;
        $scope.value2Label = function(record, key) {
            var val, i, j, s, aVal, aLab = [];
            if (schemas && record.data) {
                val = record.data[key];
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
        facRecord = Record.ins(LS.p.site, LS.p.app, LS.p.rid);
        options = {
            owner: 'U',
            rid: LS.p.rid
        };
        fnFetch = function() {
            facRecord.list(options.owner, options.rid).then(function(records) {
                $scope.records = records;
            });
        };
        $scope.like = function(event, record) {
            event.preventDefault();
            event.stopPropagation();
            facRecord.like(record).then(function(rsp) {});
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
    ngApp.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
        var facRecord = Record.ins(),
            schemas = $scope.app.data_schemas;
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
        $scope.editRecord = function(event, page) {
            page ? $scope.gotoPage(event, page, facRecord.current.enroll_key) : alert('没有指定登记编辑页');
        };
        $scope.gotoEnroll = function(event, page) {
            if ($scope.app.enroll_app_id) {
                var url = '/rest/site/fe/matter/enroll';
                url += '?site=' + LS.p.site;
                url += '&app=' + $scope.app.enroll_app_id;
                location.href = url;
            } else {
                $scope.$root.$errmsg = '没有指定关联报名表，无法填写报名信息';
            }
        };
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    }]);
    ngApp.controller('ctrlView', ['$scope', function($scope) {
        $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
            if (event.targetScope !== $scope) {
                $scope.$broadcast('xxt.app.enroll.filter.owner', data);
            }
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});