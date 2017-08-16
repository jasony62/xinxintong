'use strict';
require('./rank.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlRank', ['$scope', '$q', '$sce', 'http2', 'ls', function($scope, $q, $sce, http2, LS) {
    function list() {
        var defer = $q.defer();
        switch (oAppState.criteria.obj) {
            case 'user':
                http2.post('/rest/site/fe/matter/enroll/rank/userByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'group':
                http2.post('/rest/site/fe/matter/enroll/rank/groupByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'data':
                http2.post('/rest/site/fe/matter/enroll/rank/dataByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'remark':
                http2.post('/rest/site/fe/matter/enroll/rank/remarkByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
        }
        return defer.promise;
    }
    var oApp, oAppState, oAgreedLabel;
    oAgreedLabel = { 'Y': '推荐', 'N': '屏蔽', 'A': '' };
    /* 恢复上一次访问的状态 */
    if (window.localStorage) {
        $scope.$watch('appState', function(nv) {
            if (nv) {
                window.localStorage.setItem("site.fe.matter.enroll.rank.appState", JSON.stringify(nv));
            }
        }, true);
        if (oAppState = window.localStorage.getItem("site.fe.matter.enroll.rank.appState")) {
            oAppState = JSON.parse(oAppState);
        }
    }
    if (!oAppState) {
        oAppState = {
            criteria: {
                obj: 'user',
                orderby: 'enroll',
                agreed: 'all'
            },
            page: {
                at: 1,
                size: 12
            }
        };
    }
    $scope.appState = oAppState;
    $scope.gotoRemark = function(ek, schemaId, remarkId) {
        var url = LS.j('', 'site', 'app');
        url += '&ek=' + ek;
        url += '&schema=' + schemaId;
        remarkId && (url += '&remark=' + remarkId);
        url += '&page=remark';
        location.href = url;
    };
    $scope.doSearch = function(pageAt) {
        if (pageAt) {
            oAppState.page.at = pageAt;
        }
        list().then(function(data) {
            switch (oAppState.criteria.obj) {
                case 'user':
                    if (data.users) {
                        data.users.forEach(function(user) {
                            $scope.users.push(user);
                        });
                    }
                    break;
                case 'group':
                    if (data.groups) {
                        data.groups.forEach(function(group) {
                            $scope.groups.push(group);
                        });
                    }
                    break;
                case 'data':
                    if (data.records) {
                        data.records.forEach(function(record) {
                            if (oApp._schemasById[record.schema_id].type == 'file') {
                                record.value = angular.fromJson(record.value);
                            }
                            record._agreed = oAgreedLabel[record.agreed] || '';
                            $scope.records.push(record);
                        });
                    }
                    break;
                case 'remark':
                    if (data.remarks) {
                        data.remarks.forEach(function(remark) {
                            remark._agreed = oAgreedLabel[remark.agreed] || '';
                            $scope.remarks.push(remark);
                        });
                    }
                    break;
            }
            oAppState.page.total = data.total;
        });
    };
    $scope.changeCriteria = function() {
        $scope.users = [];
        $scope.groups = [];
        $scope.records = [];
        $scope.remarks = [];
        $scope.doSearch(1);
    };
    $scope.value2Label = function(oRecord, schemaId) {
        var value, val, schema, aVal, aLab = [];

        value = oRecord.value;
        if ((schema = $scope.app._schemasById[schemaId]) && value) {
            if (val = value) {
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
            if (oRecord.supplement) {
                val += ' （' + oRecord.supplement + '）';
            }
        }
        return $sce.trustAsHtml(val);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
            if (oNew && oOld && oNew !== oOld) {
                switch (oNew) {
                    case 'user':
                        oAppState.criteria.orderby = 'enroll';
                        break;
                    case 'group':
                        oAppState.criteria.orderby = 'enroll';
                        break;
                    case 'data':
                        oAppState.criteria.orderby = 'remark';
                        break;
                    case 'remark':
                        oAppState.criteria.orderby = '';
                        break;
                }
                $scope.changeCriteria();
            }
        });
        $scope.changeCriteria();
    });
}]);