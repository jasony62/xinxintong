'use strict';
require('./remark.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlRemark', ['$scope', '$q', 'http2', function($scope, $q, http2) {
    function listRemarks() {
        var url, defer = $q.defer();
        url = '/rest/site/fe/matter/enroll/remark/list?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        http2.get(url).then(function(rsp) {
            defer.resolve(rsp.data)
        });
        return defer.promise;
    }

    function summary() {
        var url, defer = $q.defer();
        url = '/rest/site/fe/matter/enroll/remark/summary?site=' + oApp.siteid + '&ek=' + ek;
        http2.get(url).then(function(rsp) {
            defer.resolve(rsp.data)
        });
        return defer.promise;
    }
    var oApp, ek, schemaId;
    ek = location.search.match(/[\?&]ek=([^&]*)/)[1];
    if (location.search.match(/[\?&]schema=[^&]*/)) {
        schemaId = location.search.match(/[\?&]schema=([^&]*)/)[1];
    } else {
        schemaId = null;
    }
    $scope.newRemark = {};
    $scope.filter = {};
    $scope.addRemark = function() {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/add?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        http2.post(url, $scope.newRemark).then(function(rsp) {
            $scope.remarks.push(rsp.data);
            $scope.newRemark.content = '';
        });
    };
    $scope.likeRemark = function(oRemark) {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/like';
        url += '?site=' + oApp.siteid;
        url += '&remark=' + oRemark.id;
        http2.get(url).then(function(rsp) {
            oRemark.like_log = rsp.data.like_log;
            oRemark.like_num = rsp.data.like_num;
        });
    };
    $scope.likeRecordData = function() {
        var url;
        url = '/rest/site/fe/matter/enroll/record/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + $scope.record.enroll_key;
        url += '&schema=' + $scope.filter.schema.id;
        http2.get(url).then(function(rsp) {
            $scope.data.like_log = rsp.data.like_log;
            $scope.data.like_num = rsp.data.like_num;
        });
    };
    $scope.gotoRecord = function() {
        var oPage;
        if ($scope.record.userid === $scope.user.uid) {
            for (var i in $scope.app.pages) {
                oPage = $scope.app.pages[i];
                if (oPage.type === 'V') {
                    $scope.gotoPage(null, oPage.name, $scope.record.enroll_key);
                    break;
                }
            }
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oSchema, aRemarkable = [];
        oApp = params.app;
        $scope.record = params.record;
        for (var i = 0, ii = oApp.dataSchemas.length; i < ii; i++) {
            if (oApp.dataSchemas[i].remarkable && oApp.dataSchemas[i].remarkable === 'Y') {
                aRemarkable.push(oApp.dataSchemas[i]);
            }
            if (schemaId && oApp.dataSchemas[i].id === schemaId) {
                oSchema = oApp.dataSchemas[i];
            }
        }
        if (oSchema) {
            $scope.filter.schema = oSchema;
        } else if (aRemarkable.length) {
            $scope.filter.schema = aRemarkable[0];
        }
        $scope.remarkableSchemas = aRemarkable;
    });
    $scope.$watch('filter', function(nv) {
        if (nv && nv.schema) {
            listRemarks().then(function(data) {
                $scope.data = data.data;
                $scope.remarks = data.remarks;
            });
        }
    }, true);
}]);
