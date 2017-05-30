'use strict';
require('./list.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['http2', '$q', 'ls', function(http2, $q, LS) {
    var Round, _ins;
    Round = function() {};
    Round.prototype.list = function() {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('round/list', 'site', 'app');
        http2.get(url).then(function(rsp) {
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
ngApp.factory('Record', ['http2', '$q', 'ls', function(http2, $q, LS) {
    var Record, _ins;
    Record = function() {};
    Record.prototype.list = function(owner, oCriteria) {
        var deferred = $q.defer(),
            url;
        url = LS.j('record/list', 'site', 'app');
        url += '&owner=' + owner;
        http2.post(url, oCriteria ? oCriteria : {}).then(function(rsp) {
            var records, record, i, l;
            if (rsp.err_code == 0) {
                records = rsp.data.records;
                if (records && records.length) {
                    for (i = 0, l = records.length; i < l; i++) {
                        record = records[i];
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
ngApp.controller('ctrlRecords', ['$scope', '$uibModal', 'Record', 'ls', '$sce', function($scope, $uibModal, Record, LS, $sce) {
    var facRecord, options, fnFetch,
        oApp = $scope.app,
        oCurrentCriteria = {};

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
    $scope.openFilter = function() {
        $uibModal.open({
            templateUrl: 'filter.html',
            controller: ['$scope', '$uibModalInstance', 'Round', function($scope2, $mi, Round) {
                var facRound;
                $scope2.dataSchemas = $scope.app.dataSchemas;
                $scope2.criteria = oCurrentCriteria;
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close(oCurrentCriteria);
                };
                facRound = Round.ins();
                facRound.list().then(function(result) {
                    $scope2.rounds = result.rounds;
                });
            }],
            windowClass: 'auto-height',
            backdrop: 'static',
        }).result.then(function(oCriteria) {
            facRecord.list(options.owner, oCriteria).then(function(records) {
                $scope.records = records;
            });
        });
    };
    $scope.resetFilter = function() {
        oCurrentCriteria = {};
        facRecord.list(options.owner, oCurrentCriteria).then(function(records) {
            $scope.records = records;
        });
    };
    facRecord = Record.ins();
    options = {
        owner: 'U',
    };
    fnFetch = function() {
        facRecord.list(options.owner).then(function(records) {
            $scope.records = records;
        });
    };
    $scope.$watch('options', function(nv) {
        $scope.fetch();
    }, true);
    $scope.options = options;
    $scope.fetch = fnFetch;
}]);
ngApp.controller('ctrlList', ['$scope', function($scope) {}]);
