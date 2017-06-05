'use strict';

var ngApp = require('./main.js');
ngApp.controller('ctrlRank', ['$scope', '$q', 'http2', 'ls', function($scope, $q, http2, LS) {
    function list() {
        var defer = $q.defer();
        switch (oAppState.criteria.obj) {
            case 'user':
                http2.post('/rest/site/fe/matter/enroll/rank/userByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
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
    var oApp, oAppState;
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
                orderby: 'enroll'
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
                case 'data':
                    if (data.records) {
                        data.records.forEach(function(record) {
                            $scope.records.push(record);
                        });
                    }
                    break;
                case 'remark':
                    if (data.remarks) {
                        data.remarks.forEach(function(remark) {
                            $scope.remarks.push(remark);
                        });
                    }
                    break;
            }
            oAppState.page.total = data.total;
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
            var orderbyChanged = true;
            if (oNew && oOld && oNew !== oOld) {
                switch (oNew) {
                    case 'user':
                        oAppState.criteria.orderby = 'enroll';
                        break;
                    case 'data':
                        if (oAppState.criteria.orderby === 'remark') {
                            orderbyChanged = false;
                        } else {
                            oAppState.criteria.orderby = 'remark';
                        }
                        break;
                    case 'remark':
                        oAppState.criteria.orderby = '';
                        break;
                }
                if (!orderbyChanged) {
                    $scope.users = [];
                    $scope.doSearch(1);
                }
            }
        });
        $scope.$watch('appState.criteria.orderby', function(oNew) {
            if (oNew !== undefined) {
                $scope.users = [];
                $scope.records = [];
                $scope.remarks = [];
                $scope.doSearch(1);
            }
        });
    });
}]);
