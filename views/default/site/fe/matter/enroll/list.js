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
    Record.prototype.list = function(options, oCriteria) {
        var deferred = $q.defer(),
            url;
        url = LS.j('record/list', 'site', 'app');
        url += '&' + options.j();
        http2.post(url, oCriteria ? oCriteria : {}).then(function(rsp) {
            var records, record;
            records = rsp.data.records;
            options.page.total = rsp.data.total;
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
ngApp.directive('enrollRecords', function() {
    return {
        restrict: 'A',
        replace: 'false',
        link: function(scope, ele, attrs) {
            if (scope.options && attrs.enrollRecordsOwner && attrs.enrollRecordsOwner.length) {
                scope.options.owner = attrs.enrollRecordsOwner;
            }
        }
    }
});
ngApp.controller('ctrlRecords', ['$scope', '$uibModal', 'Record', 'ls', '$sce', function($scope, $uibModal, Record, LS, $sce) {
    function fnFetch(pageAt) {
        if (pageAt) {
            options.page.at = pageAt;
        } else {
            options.page.at++;
        }
        facRecord.list(options, oCurrentCriteria).then(function(records) {
            if (options.page.at === 1) {
                $scope.records = records;
            } else {
                $scope.records = $scope.records.concat(records);
            }
            if ($scope.records) {
                $scope.records.forEach(function(record) {
                    if (record.data_tag) {
                        for (var schemaId in record.data_tag) {
                            var dataTags = record.data_tag[schemaId],
                                converted = [];
                            dataTags.forEach(function(tagId) {
                                oApp._tagsById[tagId] && converted.push(oApp._tagsById[tagId]);
                            });
                            record.data_tag[schemaId] = converted;
                        }
                    }
                    record.tag = record.data_tag ? record.data_tag : {};
                    _records.push(record);
                });
                facRecord.current = _records;
            }
        });
    }
    var facRecord, options, oApp, oActiveRound, oCurrentCriteria,
        _records = [];

    options = {
        owner: '',
        page: { at: 1, size: 12 },
        j: function() {
            return 'owner=' + this.owner + '&page=' + this.page.at + '&size=' + this.page.size;
        }
    };
    $scope.options = options;
    $scope.fetch = fnFetch;
    $scope.Record = facRecord = Record.ins();
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
            if (val && schema.ops && schema.ops.length) {
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
            fnFetch(1);
        });
    };
    $scope.resetFilter = function() {
        oCurrentCriteria = {};
        if (oActiveRound) {
            oCurrentCriteria.record = { rid: oActiveRound.rid };
        }
        fnFetch(1);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        if (params.activeRound) {
            oActiveRound = params.activeRound;
            oCurrentCriteria = { record: { rid: oActiveRound.rid } };
        }
        $scope.$watch('options.owner', function(nv) {
            if (nv) {
                $scope.fetch(1);
            }
        });
    });
}]);
ngApp.controller('ctrlList', ['$scope', function($scope) {}]);
