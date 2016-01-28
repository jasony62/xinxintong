define(["require", "angular", "angular-sanitize", "xxt-share", "enroll-directive", "enroll-common"], function(require, angular) {
    'use strict';
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
    app.factory('Record', ['$http', '$q', function($http, $q) {
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
            url = LS.j('record/get', 'mpid', 'aid');
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
            url = LS.j('record/list', 'mpid', 'aid');
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
        Record.prototype.like = function(record) {
            var url;
            deferred = $q.defer();
            url = LS.j('record/score', 'mpid');
            url += '&ek=' + record.enroll_key;
            $http.get(url).success(function(rsp) {
                record.myscore = rsp.data.myScore;
                record.score = rsp.data.score;
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        Record.prototype.likerList = function(record) {
            var url;
            deferred = $q.defer();
            url = LS.j('record/likerList', 'mpid');
            url += '&ek=' + record.enroll_key;
            $http.get(url).success(function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        Record.prototype.remark = function(record, newRemark) {
            var url, deferred;
            deferred = $q.defer();
            url = LS.j('record/remark', 'mpid');
            url += '&ek=' + record.enroll_key;
            $http.post(url, {
                remark: newRemark
            }).success(function(rsp) {
                if (angular.isString(rsp)) {
                    alert(rsp);
                    return;
                }
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                record.remarks.push(rsp.data);
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        return {
            ins: function(mpid, aid, rid, $scope) {
                if (_ins) {
                    return _ins;
                }
                _ins = new Record(mpid, aid, rid, $scope);
                return _ins;
            }
        };
    }]);
    app.factory('Statistic', ['$http', function($http) {
        var Stat = function(mpid, aid, data) {
            this.mpid = mpid;
            this.aid = aid;
            this.data = null;
            this.result = {};
        };
        Stat.prototype.rankByFollower = function() {
            var _this, url;
            _this = this;
            url = '/rest/app/enroll/rankByFollower';
            url += '?mpid=' + this.mpid;
            url += '&aid=' + this.aid;
            $http.get(url).success(function(rsp) {
                _this.result.rankByFollower = rsp.data;
            });
        };
        return Stat;
    }]);
    app.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
        var facRecord, options, fnFetch;
        facRecord = Record.ins(LS.p.mpid, LS.p.aid, LS.p.rid);
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
    app.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
        var facRecord;
        $scope.value2Label = function(key) {
            var val, schemas, i, j, s, aVal, aLab = [];
            if ($scope.Schema && $scope.Schema.data && facRecord.current.data) {
                val = facRecord.current.data[key];
                if (val === undefined) return '';
                schemas = $scope.Schema.data;
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
                        aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].label);
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
        $scope.like = function(event, nextAction) {
            event.preventDefault();
            event.stopPropagation();
            facRecord.like(facRecord.current).then(function(data) {
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    var url = LS.j('', 'mpid', 'aid');
                    url += '&ek=' + facRecord.current.enroll_key;
                    url += '&page=' + nextAction;
                    location.replace(url);
                } else {
                    alert('操作成功');
                }
            });
        };
        $scope.likers = function(event) {
            facRecord.likerList(facRecord.current).then(function(data) {
                $scope.likers = data.likers;
            });
        };
        facRecord = Record.ins();
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    }]);
    app.controller('ctrlRemark', ['$scope', '$http', 'Record', function($scope, $http, Record) {
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
    app.controller('ctrlInvite', ['$scope', '$http', 'Record', function($scope, $http, Record) {
        var facRecord;
        $scope.options = {
            genRecordWhenAccept: 'Y'
        };
        $scope.invitee = '';
        $scope.send = function(event, invitePage, nextAction) {
            event.preventDefault();
            event.stopPropagation();
            var url;
            url = LS.j('record/inviteSend', 'mpid', 'aid');
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
                    var url = LS.j('', 'mpid', 'aid');
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
            url = LS.j('record/acceptInvite', 'mpid', 'aid');
            url += '&inviter=' + inviter;
            $scope.options.genRecordWhenAccept === 'N' && (url += '&state=2');
            $http.get(url).success(function(rsp) {
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    var url = LS('', 'mpid', 'aid');
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
    app.controller('ctrlStatistic', ['$scope', '$http', function($scope, $http) {
        var fnFetch;
        fnFetch = function(options) {
            var url;
            url = LS.j('statGet', 'mpid', 'aid');
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
    app.directive('enrollStatistic', [function() {
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
    app.directive('enrollSchema', ['Schema', function(facSchema) {
        return {
            restrict: 'A',
            link: function(scope, elem, attrs) {
                var i, params, pv, options;
                params = attrs.enrollSchema.split(';');
                options = {};
                for (i in params) {
                    pv = params[i];
                    pv = pv.split('=');
                    options[pv[0]] = pv[1];
                }
                scope.Schema = facSchema.ins();
                scope.Schema.get(options);
            }
        };
    }]);
    app.controller('ctrlView', ['$scope', function($scope) {
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