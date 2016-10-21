define(["angular", "enroll-common", "angular-sanitize", "xxt-share"], function(angular, ngApp) {
    'use strict';

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
        Record.prototype.remove = function(record) {
            var deferred = $q.defer(),
                url;
            url = LS.j('record/remove', 'site', 'app');
            url += '&ek=' + record.enroll_key;
            $http.get(url).success(function(rsp) {
                deferred.resolve(rsp.data);
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
    ngApp.factory('Statistic', ['$http', function($http) {
        var Stat = function(siteId, appId, data) {
            this.siteId = siteId;
            this.appId = appId;
            this.data = null;
            this.result = {};
        };
        Stat.prototype.rankByFollower = function() {
            var _this, url;
            _this = this;
            url = '/rest/app/enroll/rankByFollower';
            url += '?site=' + this.siteId;
            url += '&app=' + this.appId;
            $http.get(url).success(function(rsp) {
                _this.result.rankByFollower = rsp.data;
            });
        };
        return Stat;
    }]);
    ngApp.controller('ctrlRecords', ['$scope', 'Record', 'ls', '$sce', function($scope, Record, LS, $sce) {
        var facRecord, options, fnFetch,
            schemas = $scope.app.data_schemas,
            schemasById = {};

        schemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        $scope.value2Label = function(record, schemaId) {
            var val, i, j, s, aVal, aLab = [];
            if (schemas && record.data) {
                val = record.data[schemaId];
                if (val === undefined) return '';
                s = schemasById[schemaId];
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
        $scope.score2Html = function(record, schemaId) {
            var label = '',
                schema = schemasById[schemaId],
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
    ngApp.controller('ctrlRemark', ['$scope', '$http', 'Record', 'ls', function($scope, $http, Record, LS) {
        var facRecord;
        $scope.newRemark = '';
        $scope.remark = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if ($scope.newRemark.length === 0) {
                alert('评论内容不允许为空');
                return false;
            }
            if (facRecord.current.enroll_key === undefined) {
                alert('没有指定要评论的登记记录');
                return false;
            }
            facRecord.remark(facRecord.current, $scope.newRemark).then(function(rsp) {
                $scope.newRemark = '';
            });
        };
        facRecord = Record.ins();
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    }]);
    ngApp.controller('ctrlInvite', ['$scope', '$http', 'Record', 'ls', function($scope, $http, Record, LS) {
        var facRecord;
        $scope.options = {
            genRecordWhenAccept: 'Y'
        };
        $scope.invitee = '';
        $scope.send = function(event, invitePage, nextAction) {
            event.preventDefault();
            event.stopPropagation();
            var url;
            url = LS.j('record/inviteSend', 'site', 'app');
            url += '&ek=' + facRecord.current.enroll_key;
            url += '&invitee=' + $scope.invitee;
            url += '&page=' + invitePage;
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    var url = LS.j('', 'site', 'app');
                    url += '&ek=' + facRecord.current.enroll_key;
                    url += '&page=' + nextAction;
                    location.replace(url);
                } else {
                    alert('操作成功');
                }
            });
        };
        $scope.accept = function(event, nextAction) {
            var inviter, url;
            if (!$scope.Record.current) {
                alert('未进行登记，无效的邀请');
                return;
            }
            if ($scope.Record.current.openid === $scope.User.fan.openid) {
                alert('不能自己邀请自己');
                return;
            }
            inviter = $scope.Record.current.enroll_key;
            url = LS.j('record/acceptInvite', 'site', 'app');
            url += '&inviter=' + inviter;
            $scope.options.genRecordWhenAccept === 'N' && (url += '&state=2');
            $http.get(url).success(function(rsp) {
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    var url = LS.j('', 'site', 'app');
                    url += '&ek=' + rsp.data.ek;
                    url += '&page=' + nextAction;
                    location.replace(url);
                }
            });
        };
        facRecord = Record.ins();
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    }]);
    ngApp.controller('ctrlStatistic', ['$scope', '$http', 'ls', function($scope, $http, LS) {
        var fnFetch;
        fnFetch = function(options) {
            var url;
            url = LS.j('statGet', 'site', 'app');
            if (options) {
                if (options.fromCache && options.fromCache === 'Y') {
                    url += '&fromCache=Y';
                    if (options.interval) {
                        url += '&interval=' + options.interval;
                    }
                }
            }
            $http.get(url).success(function(rsp) {
                $scope.statistic = rsp.data;
            });
        };
        $scope.fetch = fnFetch;
    }]);
    ngApp.directive('enrollStatistic', [function() {
        return {
            restrict: 'A',
            link: function(scope, elem, attrs) {
                var i, params, pv, options;
                params = attrs.enrollStatistic.split(';');
                options = {};
                for (i in params) {
                    pv = params[i];
                    pv = pv.split('=');
                    options[pv[0]] = pv[1];
                }
                scope.fetch(options);
            }
        };
    }]);
    ngApp.controller('ctrlView', ['$scope', function($scope) {
        $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
            if (event.targetScope !== $scope) {
                $scope.$broadcast('xxt.app.enroll.filter.owner', data);
            }
        });
    }]);

    angular._lazyLoadModule('enroll');

    return ngApp;
});