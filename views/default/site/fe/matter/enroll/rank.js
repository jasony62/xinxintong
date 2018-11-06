'use strict';
require('./rank.css');

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlRank', ['$scope', '$q', '$sce', 'http2', 'tmsLocation', 'enlRound', function($scope, $q, $sce, http2, LS, enlRound) {
    function fnRoundTitle(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get('/rest/site/fe/matter/enroll/round/get?site=' + oApp.siteid + '&app=' + oApp.id + '&rid=' + aRids).then(function(rsp) {
                if (rsp.data.length === 1) {
                    titles = rsp.data[0].title;
                } else if (rsp.data.length === 2) {
                    titles = rsp.data[0].title + ',' + rsp.data[1].title;
                } else if (rsp.data.length > 2) {
                    titles = rsp.data[0].title + '-' + rsp.data[rsp.data.length - 1].title;
                }
                defer.resolve(titles);
            });
        }
        return defer.promise;
    }

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
    oAgreedLabel = { 'Y': '推荐', 'N': '关闭', 'A': '' };
    $scope.gotoCowork = function(ek, schemaId, id, remarkId) {
        var url = LS.j('', 'site', 'app');
        url += '&ek=' + ek;
        url += '&schema=' + schemaId;
        url += '&data=' + id;
        remarkId && (url += '&remark=' + remarkId);
        url += '&page=cowork';
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
            $scope.setRound();
        } else {
            $scope.changeCriteria();
        }
    };
    /**
     * 设置轮次条件
     */
    $scope.setRound = function() {
        (new enlRound($scope.app)).pick(oAppState.criteria.round).then(function(oResult) {
            oAppState.criteria.round = oResult.ids;
            $scope.checkedRoundTitles = oResult.titles;
            $scope.changeCriteria();
        });
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
        var oConfig, rankItems, dataSchemas;
        oApp = params.app;
        dataSchemas = oApp.dynaDataSchemas;
        /* 排行显示内容设置 */
        rankItems = ['enroll', 'remark', 'like', 'remark_other', 'do_like', 'total_coin', 'score', 'average_score'];
        oConfig = {};
        rankItems.forEach(function(item) {
            oConfig[item] = true;
        });
        if (oApp.rankConfig) {
            if (oApp.rankConfig.scope) {
                rankItems.forEach(function(item) {
                    oConfig[item] = !!oApp.rankConfig.scope[item];
                });
            }
        }
        $scope.config = oConfig;
        /* 恢复上一次访问的状态 */
        if (window.localStorage) {
            $scope.$watch('appState', function(nv) {
                if (nv) {
                    window.localStorage.setItem("site.fe.matter.enroll.rank.appState", JSON.stringify(nv));
                }
            }, true);
            if (oAppState = window.localStorage.getItem("site.fe.matter.enroll.rank.appState")) {
                oAppState = JSON.parse(oAppState);
                if (!oAppState.aid || oAppState.aid !== oApp.id) {
                    oAppState = null;
                } else if (oAppState.criteria.obj === 'group') {
                    if (!oApp.entryRule.group.id) {
                        oAppState = null;
                    }
                }
            }
        }
        if (!oAppState) {
            oAppState = {
                aid: oApp.id,
                criteria: {
                    obj: oApp.rankConfig.defaultObj ? oApp.rankConfig.defaultObj : 'user',
                    orderby: oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll',
                    agreed: 'all',
                    round: ['ALL']
                },
                page: {
                    at: 1,
                    size: 12
                }
            };
        }
        (new enlRound(oApp)).getRoundTitle(oAppState.criteria.round).then(function(titles) {
            $scope.checkedRoundTitles = titles;
        });
        $scope.appState = oAppState;
        $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
            if (oNew && oOld && oNew !== oOld) {
                switch (oNew) {
                    case 'user':
                        oAppState.criteria.orderby = oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll';
                        break;
                    case 'group':
                        oAppState.criteria.orderby = oApp.rankConfig.defaultItem ? oApp.rankConfig.defaultItem : 'enroll';
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
        $scope.changeCriteria();
        /*设置页面分享信息*/
        $scope.setSnsShare();
        /*设置页面导航*/
        $scope.setPopNav(['repos', 'event'], 'rank');
        /*页面阅读日志*/
        $scope.logAccess();
        /*设置页面操作*/
        $scope.appActs = {
            addRecord: {}
        };
    });
}]);