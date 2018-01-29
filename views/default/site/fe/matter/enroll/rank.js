'use strict';
require('./rank.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['http2', '$q', function(http2, $q) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
        this.oPage = {
            at: 1,
            size: 10,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
    };
    Round.prototype.list = function() {
        var _this = this,
            deferred = $q.defer(),
            url;

        url = '/rest/site/fe/matter/enroll/round/list?site=' + this.oApp.siteid + '&app=' + this.oApp.id;
        url += this.oPage.j();
        http2.get(url).then(function(rsp) {
            _this.oPage.total = rsp.data.total;
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function(oApp) {
            _ins = _ins ? _ins : new Round(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRank', ['$scope', '$q', '$sce', 'http2', 'tmsLocation', 'Round', '$uibModal', function($scope, $q, $sce, http2, LS, srvRound, $uibModal) {
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
            case 'data-rec':
                http2.post('/rest/site/fe/matter/enroll/rank/dataByApp?site=' + oApp.siteid + '&app=' + oApp.id, { agreed: 'Y', obj: 'data-rec', orderby: oAppState.criteria.orderby, round: oAppState.criteria.round }).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'remark':
                http2.post('/rest/site/fe/matter/enroll/rank/remarkByApp?site=' + oApp.siteid + '&app=' + oApp.id, oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
            case 'remark-rec':
                http2.post('/rest/site/fe/matter/enroll/rank/remarkByApp?site=' + oApp.siteid + '&app=' + oApp.id, { agreed: 'Y', obj: 'remrak-rec', orderby: '', round: oAppState.criteria.round }).then(function(rsp) {
                    defer.resolve(rsp.data)
                });
                break;
        }
        return defer.promise;
    }
    var oApp, oAppState, oAgreedLabel;
    oAgreedLabel = { 'Y': '推荐', 'N': '屏蔽', 'A': '' };
    $scope.gotoRemark = function(ek, schemaId, id, remarkId) {
        var url = LS.j('', 'site', 'app');
        url += '&ek=' + ek;
        url += '&schema=' + schemaId;
        url += '&data=' + id;
        remarkId && (url += '&remark=' + remarkId);
        url += '&page=remark';
        location.href = url;
    };
    $scope.doSearch = function(pageAt) {
        if (pageAt) {
            oAppState.page.at = pageAt;
        }
        list().then(function(data) {
            var oSchema;
            switch (oAppState.criteria.obj) {
                case 'user':
                    if (data.users) {
                        data.users.forEach(function(user) {
                            user.headimgurl = user.headimgurl ? user.headimgurl : '/static/img/avatar.png';
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
                            if (oSchema = oApp._schemasById[record.schema_id]) {
                                if (oSchema.type == 'image') {
                                    record.value = record.value.split(',');
                                } else if (oSchema.type == 'file') {
                                    record.value = angular.fromJson(record.value);
                                }
                                record.headimgurl = record.headimgurl ? record.headimgurl : '/static/img/avatar.png';
                                record._agreed = oAgreedLabel[record.agreed] || '';
                                $scope.records.push(record);
                            }
                        });
                    }
                    break;
                case 'data-rec':
                    if (data.records) {
                        data.records.forEach(function(record) {
                            if (oSchema = oApp._schemasById[record.schema_id]) {
                                if (oSchema.type == 'image') {
                                    record.value = record.value.split(',');
                                }
                                if (oSchema.type == 'file') {
                                    record.value = angular.fromJson(record.value);
                                }
                                record.headimgurl = record.headimgurl ? record.headimgurl : '/static/img/avatar.png';
                                record._agreed = oAgreedLabel[record.agreed] || '';
                                $scope.recordsRec.push(record);
                            }
                        });
                    }
                    break;
                case 'remark':
                    if (data.remarks) {
                        data.remarks.forEach(function(remark) {
                            remark.headimgurl = remark.headimgurl ? remark.headimgurl : '/static/img/avatar.png';
                            remark._agreed = oAgreedLabel[remark.agreed] || '';
                            $scope.remarks.push(remark);
                        });
                    }
                    break;
                case 'remark-rec':
                    if (data.remarks) {
                        data.remarks.forEach(function(remark) {
                            remark.headimgurl = remark.headimgurl ? remark.headimgurl : '/static/img/avatar.png';
                            remark._agreed = oAgreedLabel[remark.agreed] || '';
                            $scope.remarksRec.push(remark);
                        });
                    }
                    break;
            }
            oAppState.page.total = data.total;
            angular.element(document).ready(function() {
                $scope.showFolder();
            });
        });
    };
    $scope.changeCriteria = function() {
        $scope.users = [];
        $scope.groups = [];
        $scope.records = [];
        $scope.recordsRec = [];
        $scope.remarks = [];
        $scope.remarksRec = [];
        $scope.doSearch(1);
    };
    $scope.doRound = function(rid) {
        if (rid == 'more') {
            $scope.moreRounds();
        } else {
            $scope.changeCriteria();
        }
    };
    $scope.moreRounds = function() {
        $uibModal.open({
            templateUrl: 'moreRound.html',
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', 'Round', function($scope2, $mi, srvRound) {
                $scope2.facRound = srvRound.ins(oApp);
                $scope2.pageOfRound = $scope2.facRound.oPage;
                $scope2.moreCriteria = {
                    rid: 'ALL'
                }
                $scope2.doSearchRound = function() {
                    if (oApp.multi_rounds === 'Y') {
                        $scope2.facRound.list().then(function(result) {
                            $scope2.activeRound = result.active;
                            $scope2.rounds = result.rounds;
                        });
                    }
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.moreCriteria.rid);
                }
                $scope2.doSearchRound();
            }]
        }).result.then(function(result) {
            $scope.appState.criteria.round = result;
            $scope.changeCriteria();
        });
    }
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
                val += '(' + oRecord.supplement + ')';
            }
        }
        return $sce.trustAsHtml(val);
    };
    $scope.showFolder = function() {
        var strBox, lastEle;
        strBox = document.querySelectorAll('.content');
        angular.forEach(strBox, function(str) {
            if (str.offsetHeight >= 43) {
                lastEle = str.parentNode.parentNode.lastElementChild;
                lastEle.classList.remove('hidden');
                str.classList.add('text-cut');
            }
        });
    }
    $scope.showStr = function(event) {
        event.preventDefault();
        event.stopPropagation();
        var checkEle = event.target.previousElementSibling.lastElementChild;
        checkEle.classList.remove('text-cut');
        event.target.classList.add('hidden');
    }
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        var remarkable, activeRound, facRound, dataSchemas = oApp.dataSchemas;
        for (var i = dataSchemas.length - 1; i >= 0; i--) {
            if (Object.keys(dataSchemas[i]).indexOf('remarkable') !== -1 && dataSchemas[i].remarkable == 'Y') {
                $scope.isRemark = true;
                break;
            }
        }
        /* 恢复上一次访问的状态 */
        if (window.localStorage) {
            $scope.$watch('appState', function(nv) {
                if (nv) {
                    window.localStorage.setItem("site.fe.matter.enroll.rank.appState", JSON.stringify(nv));
                }
            }, true);
            if (oAppState = window.localStorage.getItem("site.fe.matter.enroll.rank.appState")) {
                oAppState = JSON.parse(oAppState);
                if (oAppState.criteria.obj === 'group') {
                    if (!oApp.group_app_id) {
                        oAppState = null;
                    }
                }
            }
        }
        if (!oAppState) {
            oAppState = {
                criteria: {
                    obj: 'user',
                    orderby: 'enroll',
                    agreed: 'all',
                    round: 'ALL'
                },
                page: {
                    at: 1,
                    size: 12
                }
            };
        }
        $scope.appState = oAppState;
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
                    case 'data-rec':
                        oAppState.criteria.orderby = 'remark';
                        break;
                    case 'remark':
                        oAppState.criteria.orderby = '';
                        break;
                    case 'remark-rec':
                        oAppState.criteria.orderby = '';
                        break;
                }
                $scope.changeCriteria();
            }
        });
        $scope.facRound = facRound = srvRound.ins(oApp);
        if (oApp.multi_rounds === 'Y') {
            facRound.list().then(function(result) {
                $scope.activeRound = result.active;
                $scope.checkedRound = result.checked;
                $scope.rounds = result.rounds;
            });
        }
        $scope.changeCriteria();
    });
}]);