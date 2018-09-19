'use strict';
require('./view.css');

var ngApp = require('./main.js');
ngApp.factory('Record', ['http2', '$q', 'tmsLocation', function(http2, $q, LS) {
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
        http2.get(url).then(function(rsp) {
            var oRecord;
            oRecord = rsp.data;
            _this.current = oRecord;
            deferred.resolve(oRecord);
            _running = false;
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
ngApp.controller('ctrlRecord', ['$scope', 'Record', '$sce', 'tmsLocation', 'noticebox', function($scope, Record, $sce, LS, noticebox) {
    var facRecord = Record.ins();

    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ($scope.app.data_schemas && (schema = $scope.app._schemasById[schemaId]) && facRecord.current.data) {
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
    $scope.editRecord = function(event, page) {
        page ? $scope.gotoPage(event, page, facRecord.current.enroll_key) : noticebox.error('没有指定登记编辑页');
    };
    $scope.gotoEnroll = function(event, page) {
        if ($scope.app.enroll_app_id) {
            var url = '/rest/site/fe/matter/enroll';
            url += '?site=' + LS.s().site;
            url += '&app=' + $scope.app.enroll_app_id;
            url += '&ignoretime=Y';
            location.href = url;
        } else {
            noticebox.warn('没有指定关联报名表，无法填写报名信息');
        }
    };
    facRecord.get(LS.s().ek);
    $scope.Record = facRecord;
}]);
ngApp.controller('ctrlView', ['$scope', function($scope) {}]);