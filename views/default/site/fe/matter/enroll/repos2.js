'use strict';
require('./repos.css');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
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
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
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
ngApp.controller('ctrlRepos', ['$scope', '$sce', 'http2', 'tmsLocation', 'Round', '$timeout', function($scope, $sce, http2, LS, srvRound, $timeout) {
    var _oApp, facRound, _oPage, _oCriteria, _oShareableSchemas;
    $scope.page = _oPage = { at: 1, size: 12 };
    $scope.criteria = _oCriteria = { creator: 'all' };
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.recordList = function(pageAt) {
        var url;
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
        }
        url = LS.j('repos/recordList', 'site', 'app');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        http2.post(url, _oCriteria).then(function(result) {
            _oPage.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    var oSchema, schemaData;
                    for (var schemaId in _oShareableSchemas) {
                        oSchema = _oShareableSchemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            if ('url' === oSchema.type) {
                                schemaData._text = ngApp.oUtilSchema.urlSubstitute(schemaData);
                            }
                        }
                    }
                    $scope.repos.push(oRecord);
                });
            }
        });
    }
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=remark';
        location.href = url;
    };
    $scope.coworkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=remark';
        url += '#cowork';
        location.href = url;
    };
    $scope.recommend = function(oRecord, value) {
        var url;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site');
            url += '&ek=' + oRecord.enroll_key;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.value2Label = function(oSchema, value) {
        var val, aVal, aLab = [];
        if (val = value) {
            if (oSchema.ops && oSchema.ops.length) {
                aVal = val.split(',');
                oSchema.ops.forEach(function(op) {
                    aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                });
                val = aLab.join(',');
            }
        } else {
            val = '';
        }
        return $sce.trustAsHtml(val);
    };
    $scope.shiftRound = function() {
        $scope.recordList(1);
    };
    $scope.shiftUserGroup = function() {
        $scope.recordList(1);
    };
    $scope.shiftOwner = function() {
        $scope.recordList(1);
    };
    $scope.shiftDir = function(oDir) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDir = oDir;
        $scope.recordList(1);
    };
    /* 关闭任务提示 */
    $scope.closeTask = function(index) {
        $scope.tasks.splice(index, 1);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        _oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
            }
        });
        $scope.userGroups = params.groups;
        $scope.groupUser = params.groupUser;
        var groupOthersById = {};
        if (params.groupOthers && params.groupOthers.length) {
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
        }
        $scope.groupOthers = groupOthersById;
        $scope.recordList(1);
        $scope.facRound = facRound = srvRound.ins(_oApp);
        if (_oApp.multi_rounds === 'Y') {
            facRound.list().then(function(result) {
                if (result.active) {
                    for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                        if (result.rounds[i].rid === result.active.rid) {
                            _oCriteria.rid = result.active.rid;
                            break;
                        }
                    }
                }
                $scope.rounds = result.rounds;
            });
        }
        http2.get(LS.j('repos/dirSchemasGet', 'site', 'app')).then(function(rsp) {
            $scope.dirSchemas = rsp.data;
            if ($scope.dirSchemas && $scope.dirSchemas.length) {
                $scope.advCriteriaStatus.dirOpen = true;
            }
        });
        /*设置页面分享信息*/
        $scope.setSnsShare();
        /*设置任务提示*/
        if (_oApp.actionRule) {
            var tasks = [];
            http2.get(LS.j('repos/task', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        if (!oRule._ok) {
                            tasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, gap: oRule._no ? oRule._no[0] : 0 });
                        }
                    });
                }
            });
            $scope.tasks = tasks;
        }
        /*设置页面导航*/
        $scope.appNavs = {
            addRecord: {}
        };
        if (_oApp.can_rank === 'Y') {
            $scope.appNavs.rank = {};
        }
    });
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false,
        filterOpen: true
    };
}]);