app.factory('Round', ['$http', '$q', function($http, $q) {
    var Round = function(mpid, aid, current) {
        this.mpid = mpid;
        this.aid = aid;
        this.current = current;
        this.list = [];
    };
    Round.prototype.list2 = function() {
        var _this, deferred, promise, url;
        _this = this;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/enroll/round/list';
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Round.prototype.nextPage = function() {
        var _this = this,
            url;
        url = '/rest/app/enroll/round/list';
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.list = rsp.data;
        });
    };
    return Round;
}]);
app.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
    var facRound, onDataReadyCallbacks;
    facRound = new Round(LS.p.mpid, LS.p.aid);
    facRound.list2().then(function(rounds) {
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
    var _ins, _running;
    var Record = function(mpid, aid, rid, current, $scope) {
        this.mpid = mpid;
        this.aid = aid;
        this.rid = rid;
        this.current = current;
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.size = 10;
        this.orderBy = 'time';
        this.owner = 'all';
        this.total = -1;
        this.$scope = $scope;
    };
    var listGet = function(ins) {
        if (ins.busy) return;
        if (ins.total !== -1 && ins.total <= (ins.page - 1) * ins.size) return;
        ins.busy = true;
        var url;
        url = '/rest/app/enroll/record/';
        switch (ins.owner) {
            case 'A':
                url += 'list';
                break;
            case 'U':
                url += 'mine';
                break;
            case 'I':
                url += 'myFollowers';
                break;
            default:
                alert('没有指定要获得的登记记录类型（' + ins.owner + '）');
                return;
        }
        url += '?mpid=' + ins.mpid;
        url += '&aid=' + ins.aid;
        ins.rid !== undefined && ins.rid.length && (url += '&rid=' + ins.rid);
        url += '&orderby=' + ins.orderBy;
        url += '&page=' + ins.page;
        url += '&size=' + ins.size;
        $http.get(url).success(function(rsp) {
            var record;
            if (rsp.err_code == 0) {
                ins.total = rsp.data.total;
                if (rsp.data.records && rsp.data.records.length) {
                    for (var i = 0; i < rsp.data.records.length; i++) {
                        record = rsp.data.records[i];
                        record.data.member && (record.data.member = JSON.parse(record.data.member));
                        ins.list.push(record);
                    }
                    ins.page++;
                }
            }
            ins.busy = false;
        });
    };
    _running = false;
    Record.prototype.get = function(ek) {
        if (_running) return false;
        _running = true;
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = '/rest/app/enroll/record/get';
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
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
    Record.prototype.changeOrderBy = function(orderBy) {
        this.orderBy = orderBy;
        this.reset();
    };
    Record.prototype.reset = function() {
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.nextPage();
    };
    Record.prototype.nextPage = function(owner) {
        if (owner && this.owner !== owner) {
            this.owner = owner;
            this.reset();
        } else
            listGet(this);
    };
    Record.prototype.list2 = function(owner, rid) {
        var _this, url, deferred, promise;
        _this = this;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/app/enroll/record/';
        switch (owner) {
            case 'A':
                url += 'list';
                break;
            case 'U':
                url += 'mine';
                break;
            case 'I':
                url += 'myFollowers';
                break;
            default:
                alert('没有指定要获得的登记记录类型（' + owner + '）');
                return;
        }
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
        rid !== undefined && rid.length && (url += '&rid=' + rid);
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
        return promise;
    };
    Record.prototype.like = function(event, record) {
        event.preventDefault();
        event.stopPropagation();
        if (!record && !this.current) {
            alert('没有指定要点赞的登记记录');
            return;
        }
        var url = '/rest/app/enroll/record/score';
        url += '?mpid=' + this.mpid;
        url += '&ek=';
        record === undefined && (record = this.current);
        url += record.enroll_key;
        $http.get(url).success(function(rsp) {
            record.myscore = rsp.data[0];
            record.score = rsp.data[1];
        });
    };
    Record.prototype.remark = function(event, newRemark) {
        event.preventDefault();
        event.stopPropagation();
        if (!newRemark || newRemark.length === 0) {
            alert('评论内容不允许为空');
            return false;
        }
        var _this = this;
        if (this.current.enroll_key === undefined) {
            alert('没有指定要评论的登记记录');
            return false;
        }
        var url = '/rest/app/enroll/record/remark';
        url += '?mpid=' + this.mpid;
        url += '&ek=' + this.current.enroll_key;
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
            _this.current.remarks.push(rsp.data);
        });
        return true;
    };
    return {
        ins: function(mpid, aid, rid, current, $scope) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(mpid, aid, rid, current, $scope);
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
app.factory('Schema', ['$http', '$q', function($http, $q) {
    var schema, Schema;
    schema = null;
    Schema = function() {};
    Schema.prototype.get = function() {
        var deferred, promise;
        deferred = $q.defer();
        promise = deferred.promise;
        if (schema !== null)
            deferred.resolve(schema);
        else {
            $http.get(LS.j('page/schemaGet', 'mpid', 'aid') + '&byPage=N').success(function(rsp) {
                schema = rsp.data;
                deferred.resolve(schema);
            });
        }
        return promise;
    };
    return Schema;
}]);
app.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
    var facRecord, options, fnFetch;
    facRecord = new Record(LS.p.mpid, LS.p.aid, LS.p.rid);
    options = {
        owner: 'A',
        rid: LS.p.rid
    };
    fnFetch = function() {
        facRecord.list2(options.owner, options.rid).then(function(records) {
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
app.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
    var facRecord;
    $scope.editRecord = function(event, page) {
        var first;
        if (page === undefined && (first = PG.firstInput($scope.params.app.pages)))
            page = first.name;
        page ? $scope.gotoPage(event, page, $scope.Record.current.enroll_key) : alert('当前活动没有包含数据登记页');
    };
    facRecord = Record.ins(LS.p.mpid, LS.p.aid);
    facRecord.get(LS.p.ek);
    $scope.Record = facRecord;
}]);
app.controller('ctrlView', ['$scope', '$http', '$timeout', '$q', 'Round', 'Record', 'Statistic', 'Schema', function($scope, $http, $timeout, $q, Round, Record, Statistic, Schema) {
    $scope.likeRecord = function(event) {
        $scope.Record.like(event);
    };
    $scope.newRemark = '';
    $scope.remarkRecord = function(event) {
        if ($scope.Record.remark(event, $scope.newRemark))
            $scope.newRemark = '';
    };
    $scope.acceptInvite = function(event, nextAction) {
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
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.owner', data);
        }
    });
}]);
app.filter('value2Label', ['Schema', function(Schema) {
    var schemas;
    (new Schema()).get().then(function(data) {
        schemas = data;
    });
    return function(val, key) {
        var i, j, s, aVal, aLab = [];
        console.log('xxxx', val);
        if (val === undefined) return '';
        //if (!schemas) return '';
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
    };
}]);