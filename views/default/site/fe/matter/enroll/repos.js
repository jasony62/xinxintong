'use strict';
require('./repos.css');

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
ngApp.controller('ctrlRepos', ['$scope', 'http2', 'Round', '$sce', function($scope, http2, srvRound, $sce) {
    var oApp, facRound, page, criteria, schemas, userGroups, _items;
    _items = {};
    $scope.schemaCount = 0;
    $scope.page = page = { at: 1, size: 12 };
    $scope.criteria = criteria = { owner: 'all' };
    $scope.schemas = schemas = {};
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
                    if (schemas[oRecord.schema_id].type == 'file') {
                        oRecord.value = angular.fromJson(oRecord.value);
                    }
                    if(schemas[oRecord.schema_id].type == 'multitext') {
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
    $scope.gotoRemark = function(oRecordData, id) {
        var url;
        url = '/rest/site/fe/matter/enroll?site=' + oApp.siteid + '&app=' + oApp.id + '&page=remark';
        url += '&ek=' + oRecordData.enroll_key;
        url += '&schema=' + oRecordData.schema_id;
        url += '&id=' + id;
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
    $scope.shiftAgreed = function() {
        $scope.list4Schema(1);
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
        url = '/rest/site/fe/matter/enroll/record/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + oRecord.enroll_key;
        url += '&schema=' + oRecord.schema_id;
        url += '&id=' + id;

        http2.get(url).then(function(rsp) {
            if(schemas[oRecord.schema_id].type=='multitext'&&oRecord._items[id]) {
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
    $scope.value2Label = function(value, schemaId) {
        var val, schema, aVal, aLab = [];

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
        }
        return $sce.trustAsHtml(val);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                schemas[schema.id] = schema;
                $scope.schemaCount++;
            }
            if (schema.id === '_round_id' && schema.ops && schema.ops.length) {
                schema.ops.forEach(function(op) {
                    userGroups.push(op);
                });
            }
        });
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
    });
}]);