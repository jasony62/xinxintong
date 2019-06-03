'use strict';

var ngMod = angular.module('history.ui.enroll', []);
ngMod.factory('enlHistory', ['http2', '$q', '$uibModal', 'tmsLocation', function (http2, $q, $uibModal, LS) {
    var History;
    History = function () {};
    History.prototype.show = function (oSchema, oRecord) {
        if (!oSchema || !oRecord || !oRecord.userid) return;
        return $uibModal.open({
            template: require('./history.html'),
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                function fnGetHistory() {
                    var defer;
                    defer = $q.defer();
                    http2.post('/rest/site/fe/matter/enroll/repos/list4Schema?site=' + LS.s().site + '&app=' + LS.s().app, oCriteria, oOptions).then(function (rsp) {
                        var oBeforeRecDat;
                        if (rsp.data.records && rsp.data.records.length)
                            rsp.data.records.forEach(function (oRecDat) {
                                oBeforeRecDat = {
                                    value: oRecDat.value
                                };
                                if (oRecDat.enroll_key === oRecord.enroll_key)
                                    oBeforeRecDat.current = true;
                                records.push(oBeforeRecDat);
                            });
                        defer.resolve();
                    });
                    return defer.promise;
                }
                var oOptions = {
                    page: {}
                };
                var oCriteria = {
                    schema: oSchema.id,
                    owner: oRecord.userid,
                    rid: 'all'
                };
                var records;
                $scope2.records = records = [];
                fnGetHistory();
                $scope2.ok = function () {};
                $scope2.cancel = function () {
                    $mi.dismiss('cancel');
                };
            }]
        }).result;
    };
    return History;
}]);