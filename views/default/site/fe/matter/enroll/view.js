'use strict';
require('!style-loader!css-loader!./view.css');

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
ngApp.factory('Record', ['$http', '$q', 'ls', function($http, $q, LS) {
    var Record, _ins, _running;
    Record = function(oApp) {
        var data = {}; // 初始化空数据，优化加载体验
        oApp.dataSchemas.forEach(function(schema) {
            data[schema.id] = '';
        });
        this.current = {
            enroll_at: 0,
            data: data
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
        ins: function(oApp) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecord', ['$scope', 'Record', 'ls', '$sce', function($scope, Record, LS, $sce) {
    var facRecord;

    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId]) && facRecord.current.data) {
            if (val = facRecord.current.data[schemaId]) {
                if (schema.ops && schema.ops.length) {
                    aVal = val.split(',');
                    schema.ops.forEach(function(op) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    val = aLab.join(',');
                }
            } else {
                val = '';
            }
        }
        return $sce.trustAsHtml(val);
    };
    $scope.score2Html = function(schemaId) {
        var label = '',
            schema = $scope.app._schemasById[schemaId],
            val;

        if (schema && facRecord.current.data && facRecord.current.data[schemaId]) {
            val = facRecord.current.data[schemaId];
            if (schema.ops && schema.ops.length) {
                schema.ops.forEach(function(op, index) {
                    label += '<div>' + op.l + ': ' + (val[op.v] ? val[op.v] : 0) + '</div>';
                });
            }
        }
        return $sce.trustAsHtml(label);
    };
    $scope.editRecord = function(event, page) {
        page ? $scope.gotoPage(event, page, facRecord.current.enroll_key) : alert('没有指定登记编辑页');
    };
    $scope.remarkRecord = function(event) {
        $scope.gotoPage(event, 'remark', facRecord.current.enroll_key);
    };
    $scope.removeRecord = function(event, page) {
        facRecord.remove(facRecord.current).then(function(data) {
            page && $scope.gotoPage(event, page);
        });
    };
    $scope.$watch('app', function(app) {
        if (!app) return;
        facRecord = Record.ins(app);
        facRecord.get(LS.p.ek);
        $scope.Record = facRecord;
    });
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
ngApp.controller('ctrlView', ['$scope', function($scope) {}]);
