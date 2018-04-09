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
ngApp.controller('ctrlRepos', ['$scope', 'tmsLocation', 'http2', 'Round', '$sce', function($scope, LS, http2, srvRound, $sce) {
    var oApp, facRound, page, criteria, shareableSchemas, userGroups, _items;
    _items = {};
    $scope.schemaCount = 0;
    $scope.page = page = { at: 1, size: 12 };
    $scope.criteria = criteria = { owner: 'all' };
    $scope.schemas = shareableSchemas = {};
    $scope.userGroups = userGroups = [];
    $scope.repos = [];
    $scope.clickAdvCriteria = function(event) {
        event.preventDefault();
        event.stopPropagation();
    };
    $scope.list4Schema = function(pageAt) {
        var url;
        if (pageAt) {
            page.at = pageAt;
        } else {
            page.at++;
        }
        if (page.at == 1) {
            $scope.repos = [];
        }
        url = '/rest/site/fe/matter/enroll/repos/list4Schema?site=' + oApp.siteid + '&app=' + oApp.id;
        url += '&page=' + page.at + '&size=' + page.size;
        http2.post(url, criteria).then(function(result) {
            page.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    if (/file|url/.test(shareableSchemas[oRecord.schema_id].type)) {
                        oRecord.value = angular.fromJson(oRecord.value);
                        if ('url' === shareableSchemas[oRecord.schema_id].type) {
                            oRecord.value._text = ngApp.oUtilSchema.urlSubstitute(oRecord.value);
                        }
                    } else if (shareableSchemas[oRecord.schema_id].type === 'multitext') {
                        angular.forEach(oRecord.items, function(item) {
                            _items[item.id] = item;
                        });
                        oRecord._items = _items;
                    }
                    if (oRecord.tag) {
                        oRecord.tag.forEach(function(index, tagId) {
                            if (oApp._tagsById[index]) {
                                oRecord.tag[tagId] = oApp._tagsById[index];
                            }
                        });
                    }
                    $scope.repos.push(oRecord);
                });
            }
        });
    }
    $scope.gotoCowork = function(oRecordData, id) {
        var url;
        url = '/rest/site/fe/matter/enroll?site=' + oApp.siteid + '&app=' + oApp.id + '&page=cowork';
        url += '&ek=' + oRecordData.enroll_key;
        url += '&schema=' + oRecordData.schema_id;
        url += '&data=' + id;
        location.href = url;
    };
    $scope.shiftRound = function() {
        if (criteria.rid === 'more') {
            facRound.oPage.at++;
            facRound.list().then(function(result) {
                result.rounds.forEach(function(round) {
                    $scope.rounds.push(round);
                })
            });
        } else {
            $scope.list4Schema(1);
        }
    };
    $scope.shiftUserGroup = function() {
        $scope.list4Schema(1);
    };
    $scope.shiftOwner = function() {
        $scope.list4Schema(1);
    };
    $scope.shiftSchema = function() {
        $scope.list4Schema(1);
    };
    $scope.shiftTag = function() {
        $scope.list4Schema(1);
    };
    $scope.likeRecordData = function(oRecord, id, index) {
        var url;
        url = '/rest/site/fe/matter/enroll/data/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + oRecord.enroll_key;
        url += '&schema=' + oRecord.schema_id;
        url += '&data=' + id;

        http2.get(url).then(function(rsp) {
            if (shareableSchemas[oRecord.schema_id].type == 'multitext' && oRecord._items[id]) {
                oRecord.items[index].like_log = rsp.data.itemLike_log;
                oRecord.items[index].like_num = rsp.data.itemLike_num;
            }
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
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
    $scope.recommend = function(oRecData, value) {
        var url;
        if (oRecData.agreed !== value) {
            url = '/rest/site/fe/matter/enroll/data/agree';
            url += '?site=' + oApp.siteid;
            url += '&ek=' + oRecData.enroll_key;
            url += '&schema=' + oRecData.schema_id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecData.agreed = value;
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
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                shareableSchemas[schema.id] = schema;
                $scope.schemaCount++;
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
        $scope.dataTags = oApp.dataTags;
        $scope.list4Schema(1);
        $scope.facRound = facRound = srvRound.ins(oApp);
        if (oApp.multi_rounds === 'Y') {
            facRound.list().then(function(result) {
                if (result.active) {
                    for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                        if (result.rounds[i].rid === result.active.rid) {
                            criteria.rid = result.active.rid;
                            break;
                        }
                    }
                }
                $scope.rounds = result.rounds;
            });
        }
        /*设置页面分享信息*/
        $scope.setSnsShare();
        /*设置页面导航*/
        $scope.appActs = {
            addRecord: {}
        };
        if (oApp.can_rank === 'Y') {
            $scope.appNavs = { rank: {} };
        }
    });
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false,
        filterOpen: true
    };
}]);