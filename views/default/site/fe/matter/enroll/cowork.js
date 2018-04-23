'use strict';
require('./cowork.css');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.controller('ctrlCowork', ['$scope', '$timeout', '$location', '$anchorScroll', '$sce', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'tmsDynaPage', function($scope, $timeout, $location, $anchorScroll, $sce, $uibModal, LS, http2, noticebox, tmsDynaPage) {
    function listRemarks() {
        http2.get(LS.j('remark/list', 'site', 'ek', 'schema', 'data')).then(function(rsp) {
            var remarks, oRemark, oUpperRemark, oRemarks;
            remarks = rsp.data.remarks;
            if (remarks && remarks.length) {
                oRemarks = {};
                remarks.forEach(function(oRemark) {
                    oRemarks[oRemark.id] = oRemark;
                });
                for (var i = remarks.length - 1; i >= 0; i--) {
                    oRemark = remarks[i];
                    if (oRemark.content) {
                        oRemark.content = oRemark.content.replace(/\n/g, '<br/>');
                    }
                    if (oRemark.data) {
                        oRemark.reply = '<a href="#item-' + oRemark.data.id + '">回复' + oRemark.nickname + '的' + ($scope.schemasById[oRemark.data.schema_id] ? $scope.schemasById[oRemark.data.schema_id].title : '数据') + ' #' + oRemark.data.multitext_seq + '</a>';
                    } else if (oRemark.remark_id !== '0') {
                        if (oUpperRemark = oRemarks[oRemark.remark_id]) {
                            oRemark.reply = '<a href="#remark-' + oRemark.remark_id + '">回复' + oUpperRemark.nickname + '的留言 #' + ($scope.bRemarkRecord ? oUpperRemark.seq_in_record : oUpperRemark.seq_in_data) + '</a>';
                        }
                    }
                }
            }
            $scope.remarks = remarks;
            if ($location.hash() === 'remarks') {
                $timeout(function() {
                    $anchorScroll.yOffset = 30;
                    $anchorScroll();
                });
            } else if (/remark-.+/.test($location.hash())) {
                $timeout(function() {
                    var elRemark;
                    $anchorScroll();
                    elRemark = document.querySelector('#' + $location.hash());
                    elRemark.classList.toggle('blink', true);
                    $timeout(function() {
                        elRemark.classList.toggle('blink', false);
                    }, 1000);
                });
            }
        });
    }

    function addRemark(content, oRemark) {
        var url;
        url = LS.j('remark/add', 'site', 'ek', 'data');
        if (oRemark) {
            url += '&remark=' + oRemark.id;
        }
        return http2.post(url, { content: content });
    }

    function fnAfterLoad(oRecord) {
        /*设置任务提示*/
        if (_oApp.actionRule) {
            var oCoworkRule;
            oCoworkRule = $scope.ruleCowork(oRecord);
            if (oCoworkRule) {
                $scope.coworkTasks.push({ type: 'info', msg: oCoworkRule.desc, id: 'record.cowork.pre' });
            }
        }
        /*设置页面操作*/
        $scope.appActs = {
            addRecord: {}
        };
        /*设置页面导航*/
        var oAppNavs = {};
        if (_oApp.can_repos === 'Y') {
            oAppNavs.repos = {};
        }
        if (_oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_action === 'Y') {
            oAppNavs.action = {};
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    }
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (oUser.is_leader) {
            if (oUser.is_leader === 'S') {
                return true;
            }
            if (oUser.is_leader === 'Y') {
                if (oUser.group_id === oRecord.group_id) {
                    return true;
                } else if (oUser.is_editor && oUser.is_editor === 'Y') {
                    return true;
                }
            }
        }
        return false;
    }

    if (!LS.s().ek) {
        noticebox.error('参数不完整');
        return;
    }
    var _oApp, _oUser, aShareable, ek, _schemaId, _recDataId;
    ek = LS.s().ek;
    _schemaId = LS.s().schema;
    _recDataId = LS.s().data;
    $scope.coworkTasks = [];
    $scope.remarkTasks = [];
    $scope.newRemark = {};
    $scope.ruleCowork = function(oRecord) {
        var desc, gap;
        if (_oApp.actionRule) {
            var actionRule;
            actionRule = _oApp.actionRule;
            if (actionRule.record && actionRule.record.cowork && actionRule.record.cowork.pre) {
                if (actionRule.record.cowork.pre.record && actionRule.record.cowork.pre.record.likeNum) {
                    if (actionRule.record.cowork.pre.record.likeNum > oRecord.like_num) {
                        gap = actionRule.record.cowork.pre.record.likeNum - oRecord.like_num;
                        if (actionRule.record.cowork.pre.desc) {
                            desc = actionRule.record.cowork.pre.desc;
                        }
                    }
                }
            }
        }
        if (!desc) {
            return false;
        }
        return { desc: desc, gap: gap };
    };
    $scope.setAgreed = function(value) {
        var url, oRecord, oRecData;
        if ($scope.bRemarkRecord) {
            oRecord = $scope.record;
            if (oRecord.agreed !== value) {
                url = LS.j('record/agree', 'site', 'ek');
                url += '&value=' + value;
                http2.get(url).then(function(rsp) {
                    oRecord.agreed = value;
                });
            }
        } else {
            oRecData = $scope.data;
            if (oRecData.agreed !== value) {
                url = LS.j('data/agree', 'site', 'ek', 'schema');
                url += '&value=' + value;
                http2.get(url).then(function(rsp) {
                    oRecData.agreed = value;
                });
            }
        }
    };
    $scope.agreeRemark = function(oRemark, value) {
        var url;
        if (oRemark.agreed !== value) {
            url = LS.j('remark/agree', 'site');
            url += '&remark=' + oRemark.id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRemark.agreed = rsp.data;
            });
        }
    };
    $scope.likeRemark = function(oRemark) {
        var url;
        url = LS.j('remark/like', 'site');
        url += '&remark=' + oRemark.id;
        http2.get(url).then(function(rsp) {
            oRemark.like_log = rsp.data.like_log;
            oRemark.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkAsCowork = function(oRemark) {
        var url, oSchema;
        url = LS.j('remark/asCowork', 'site');
        url += '&remark=' + oRemark.id;
        if ($scope.coworkSchemas.length === 1) {
            oSchema = $scope.coworkSchemas[0];
            url += '&schema=' + oSchema.id;
            http2.get(url).then(function(rsp) {
                var oItem;
                oItem = rsp.data;
                $scope.record.verbose[oSchema.id].value.push(oItem);
                $location.hash('item-' + oItem.id);
                $timeout(function() {
                    var elItem;
                    $anchorScroll();
                    elItem = document.querySelector('#item-' + oItem.id);
                    elItem.classList.toggle('blink', true);
                    $timeout(function() {
                        elItem.classList.toggle('blink', false);
                    }, 1000);
                });
            });
        } else {
            alert('需要指定对应的题目！');
        }
    };
    $scope.writeRemark = function(oUpperRemark) {
        var remarkRemarks;
        if ($scope.remarks && $scope.remarks.length) {
            remarkRemarks = [];
            if (oUpperRemark) {
                $scope.remarks.forEach(function(oRemark) {
                    if (oRemark.remark_id && oRemark.remark_id === oUpperRemark.id) {
                        remarkRemarks.push(oRemark);
                    }
                });
            } else {
                $scope.remarks.forEach(function(oRemark) {
                    if (oRemark.remark_id === '0' && oRemark.data_id === '0') {
                        remarkRemarks.push(oRemark);
                    }
                });
            }
        }
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.remarks = remarkRemarks;
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            windowClass: 'model-remark',
            backdrop: 'static',
        }).result.then(function(data) {
            addRemark(data.content, oUpperRemark).then(function(rsp) {
                var oNewRemark;
                oNewRemark = rsp.data;
                oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                if (oUpperRemark) {
                    oNewRemark.content = '<a href="" ng-click="gotoUpper(' + oUpperRemark.id + ')">回复 ' + oUpperRemark.nickname + ' 的留言：</a><br/>' + oNewRemark.content;
                }
                $scope.remarks.push(oNewRemark);
                if (!oUpperRemark) {
                    if ($scope.bRemarkRecord) {
                        $scope.record.rec_remark_num++;
                    }
                }
                $timeout(function() {
                    var elRemark;
                    $location.hash('remark-' + oNewRemark.id);
                    $anchorScroll();
                    elRemark = document.querySelector('#remark-' + oNewRemark.id);
                    elRemark.classList.toggle('blink', true);
                    $timeout(function() {
                        elRemark.classList.toggle('blink', false);
                    }, 1000);
                });
            });
        });
    };
    $scope.editRemark = function(oRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oRemark.content
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/update', 'site') + '&remark=' + oRemark.id, { content: data.content }).then(function(rsp) {
                oRemark.content = data.content;
            });
        });
    };
    $scope.removeRemark = function(oRemark) {
        noticebox.confirm('撤销留言，确定？').then(function() {
            http2.post(LS.j('remark/remove', 'site') + '&remark=' + oRemark.id).then(function(rsp) {
                $scope.remarks.splice($scope.remarks.indexOf(oRemark), 1);
            });
        });
    };
    $scope.likeRecord = function() {
        var oRecord, oRecData;
        if ($scope.bRemarkRecord) {
            oRecord = $scope.record;
            http2.get(LS.j('record/like', 'site', 'ek')).then(function(rsp) {
                oRecord.like_log = rsp.data.like_log;
                oRecord.like_num = rsp.data.like_num;
            });
        } else {
            oRecData = $scope.record.verbose[_schemaId];
            http2.get(LS.j('data/like', 'site', 'data')).then(function(rsp) {
                oRecData.like_log = rsp.data.like_log;
                oRecData.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.editRecord = function(event) {
        if ($scope.record.userid !== $scope.user.uid) {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        var page;
        for (var i in $scope.app.pages) {
            var oPage = $scope.app.pages[i];
            if (oPage.type === 'I') {
                page = oPage.name;
                break;
            }
        }
        $scope.gotoPage(event, page, $scope.record.enroll_key);
    };
    $scope.likeItem = function(oItem) {
        http2.get(LS.j('data/like', 'site') + '&data=' + oItem.id).then(function(rsp) {
            oItem.like_log = rsp.data.like_log;
            oItem.like_num = rsp.data.like_num;
        });
    };
    $scope.gotoUpper = function(upperId) {
        var elRemark, offsetTop, parentNode;
        elRemark = document.querySelector('#remark-' + upperId);
        offsetTop = elRemark.offsetTop;
        parentNode = elRemark.parentNode;
        while (parentNode && parentNode.tagName !== 'BODY') {
            offsetTop += parentNode.offsetTop;
            parentNode = parentNode.parentNode;
        }
        document.body.scrollTop = offsetTop - 40;
        elRemark.classList.add('blink');
        $timeout(function() {
            elRemark.classList.remove('blink');
        }, 1000);
    };
    $scope.value2Label = function(oSchema) {
        var val, aVal, aLab = [];
        if ($scope.record) {
            if ($scope.record.verbose[oSchema.id]) {
                if (val = $scope.record.verbose[oSchema.id].value) {
                    if (oSchema.ops && oSchema.ops.length) {
                        aVal = val.split(',');
                        oSchema.ops.forEach(function(op) {
                            aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                        });
                        val = aLab.join(',');
                    }
                }
            }
        }
        return val ? $sce.trustAsHtml(val) : '';
    };
    /* 关闭任务提示 */
    $scope.closeCoworkTask = function(index) {
        $scope.coworkTasks.splice(index, 1);
    };
    $scope.closeRemarkTask = function(index) {
        $scope.remarkTasks.splice(index, 1);
    };
    $scope.shareRemark = function(oRemark) {
        location.href = LS.j('', 'site', 'app', 'ek') + '&remark=' + oRemark.id + '&page=share';
    };
    $scope.bRemarkRecord = !_schemaId; // 留言记录还是数据
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oAssignedSchema, oSchemasById, aCoworkSchemas;
        _oApp = params.app;
        _oUser = params.user;
        aShareable = [];
        aCoworkSchemas = [];
        oSchemasById = {};
        _oApp.dataSchemas.forEach(function(oSchema) {
            if (oSchema.shareable && oSchema.shareable === 'Y') {
                aShareable.push(oSchema);
            }
            if ($scope.bRemarkRecord && oSchema.cowork === 'Y') {
                aCoworkSchemas.push(oSchema);
            }
            if (oSchema.id === LS.s().schema) {
                oAssignedSchema = oSchema;
            }
            oSchemasById[oSchema.id] = oSchema;
        });
        $scope.schemasById = oSchemasById;
        /**
         * 分组信息
         */
        var groupOthersById;
        $scope.groupUser = params.groupUser;
        if (params.groupOthers && params.groupOthers.length) {
            groupOthersById = {};
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
            $scope.groupOthers = groupOthersById;
        }
        if ($scope.bRemarkRecord) {
            /**
             * 整条记录的留言
             */
            http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function(rsp) {
                var oRecord, aVisibleSchemas;
                $scope.record = oRecord = rsp.data;
                $scope.record._canAgree = fnCanAgreeRecord(oRecord, _oUser);
                aVisibleSchemas = [];
                aShareable.forEach(function(oSchema) {
                    if (aCoworkSchemas.indexOf(oSchema) === -1) {
                        var oSchemaData;
                        if (oSchemaData = oRecord.verbose[oSchema.id]) {
                            if (!angular.isArray(oSchemaData) || oSchemaData.length) {
                                switch (oSchema.type) {
                                    case 'longtext':
                                        oRecord.verbose[oSchema.id].value = ngApp.oUtilSchema.txtSubstitute(oRecord.verbose[oSchema.id].value);
                                        break;
                                    case 'file':
                                    case 'voice':
                                    case 'url':
                                        oRecord.verbose[oSchema.id].value = angular.fromJson(oRecord.verbose[oSchema.id].value);
                                        if ('url' === oSchema.type) {
                                            oRecord.verbose[oSchema.id].value._text = ngApp.oUtilSchema.urlSubstitute(oRecord.verbose[oSchema.id].value);
                                        } else if (/file|voice/.test(oSchema.type)) {
                                            oRecord.verbose[oSchema.id].value.forEach(function(oFile) {
                                                if (oFile.url) {
                                                    oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                                }
                                            });
                                        }
                                        break;
                                    case 'image':
                                        oRecord.verbose[oSchema.id].value = oRecord.verbose[oSchema.id].value.split(',');
                                        break;
                                    case 'single':
                                    case 'multiple':
                                        oRecord.verbose[oSchema.id].value = $scope.value2Label(oSchema);
                                        break;
                                }
                                aVisibleSchemas.push(oSchema);
                            }
                        }
                    }
                });
                /* 设置页面分享信息 */
                $scope.setSnsShare(oRecord);
                $scope.visibleSchemas = aVisibleSchemas;
                /* 加载协作填写数据 */
                var anchorItemId;
                if (/item-.+/.test($location.hash())) {
                    anchorItemId = $location.hash().substr(5);
                }
                $scope.coworkSchemas = aCoworkSchemas;
                aCoworkSchemas.forEach(function(oSchema) {
                    http2.get(LS.j('cowork/task', 'site', 'app', 'ek') + '&schema=' + oSchema.id).then(function(rsp) {
                        if (rsp.data && rsp.data.length) {
                            rsp.data.forEach(function(oRule) {
                                $scope.coworkTasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, coin: oRule.coin ? oRule.coin : 0 });
                            });
                        }
                        http2.get(LS.j('data/get', 'site', 'ek') + '&schema=' + oSchema.id + '&cascaded=Y', { autoBreak: false, autoNotice: false }).then(function(rsp) {
                            var oRecData, bRequireAnchorScroll;
                            if (rsp.data.verbose && rsp.data.verbose[oSchema.id]) {
                                oRecData = oRecord.verbose[oSchema.id];
                                if (oRecData) {
                                    oRecData.value = rsp.data.verbose[oSchema.id].items;
                                    oRecData.value.forEach(function(oItem) {
                                        if (oItem.userid !== $scope.user.uid) {
                                            oItem._others = true;
                                        }
                                        if (anchorItemId && oItem.id === anchorItemId) {
                                            bRequireAnchorScroll = true;
                                        }
                                    });
                                    if (bRequireAnchorScroll) {
                                        $timeout(function() {
                                            var elItem;
                                            $anchorScroll();
                                            elItem = document.querySelector('#item-' + anchorItemId);
                                            elItem.classList.toggle('blink', true);
                                            $timeout(function() {
                                                elItem.classList.toggle('blink', false);
                                            }, 1000);
                                        });
                                    }
                                }
                            }
                        });
                    });
                });
                http2.get(LS.j('remark/task', 'site', 'app') + '&ek=' + oRecord.enroll_key).then(function(rsp) {
                    if (rsp.data && rsp.data.length) {
                        rsp.data.forEach(function(oRule) {
                            $scope.remarkTasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, coin: oRule.coin ? oRule.coin : 0 });
                        });
                    }
                });
                //
                listRemarks();
                /* 结束数据加载后的处理 */
                fnAfterLoad(oRecord);
            });
        } else {
            /**
             * 单道题目的留言
             */
            http2.get(LS.j('data/get', 'site', 'ek', 'schema', 'data')).then(function(rsp) {
                var oRecord, oRecData;
                if (oRecord = rsp.data) {
                    if (oRecData = oRecord.verbose[LS.s().schema]) {
                        if (/file|url/.test(oAssignedSchema.type)) {
                            oRecData.value = angular.fromJson(oRecData.value);
                            if ('url' === oAssignedSchema.type) {
                                oRecData.value._text = ngApp.oUtilSchema.urlSubstitute(oRecData.value);
                            }
                        } else if (/image/.test(oAssignedSchema.type)) {
                            oRecData.value = oRecData.value.split(',');
                        }
                        if (oRecData.tag) {
                            oRecData.tag.forEach(function(index, tagId) {
                                if (_oApp._tagsById[index]) {
                                    oRecData.tag[tagId] = _oApp._tagsById[index];
                                }
                            });
                        }
                    }
                    $scope.record = oRecord;
                    $scope.record._canAgree = fnCanAgreeRecord(oRecord, _oUser);
                    $scope.data = oRecData;
                    listRemarks();
                    tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
                    /*设置页面分享信息*/
                    $scope.setSnsShare(oRecord, { 'schema': LS.s().schema, 'data': LS.s().data });
                }
                /* 结束数据加载后的处理 */
                fnAfterLoad(oRecord);
            });
            $scope.visibleSchemas = [oAssignedSchema];
        }
    });
}]);
/**
 * 协作题
 */
ngApp.controller('ctrlCoworkData', ['$scope', '$timeout', '$anchorScroll', '$uibModal', 'tmsLocation', 'http2', 'noticebox', function($scope, $timeout, $anchorScroll, $uibModal, LS, http2, noticebox) {
    $scope.addItem = function(oSchema) {
        var oCoworkRule;
        if (oCoworkRule = $scope.ruleCowork($scope.record)) {
            noticebox.warn(oCoworkRule.desc);
            return;
        }
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            var oRecData, oNewItem, url;
            oRecData = $scope.record.verbose[oSchema.id];
            oNewItem = {
                value: data.content
            };
            url = LS.j('cowork/add', 'site');
            if (oRecData) {
                url += '&data=' + oRecData.id;
            } else {
                url += '&ek=' + $scope.record.enroll_key + '&schema=' + oSchema.id;
            }
            http2.post(url, oNewItem).then(function(rsp) {
                if (oRecData) {
                    oRecData.value.push(rsp.data[0]);
                } else {
                    oRecData = $scope.record.verbose[oSchema.id] = rsp.data[1];
                    oRecData.value = [rsp.data[0]];
                }
            });
        });
    };
    $scope.editItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.value[index];
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oItem.value
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            var oNewItem;
            oNewItem = {
                value: data.content
            };
            http2.post(LS.j('cowork/update', 'site') + '&data=' + oRecData.id + '&item=' + oItem.id, oNewItem).then(function(rsp) {
                oItem.value = data.content;
            });
        });
    };
    $scope.removeItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.value[index];
        noticebox.confirm('删除填写项，确定？').then(function() {
            http2.get(LS.j('cowork/remove', 'site') + '&data=' + oRecData.id + '&item=' + oItem.id).then(function(rsp) {
                oRecData.value.splice(index, 1);
            });
        });
    };
    $scope.agreeItem = function(oItem, value) {
        var url;
        if (oItem.agreed !== value) {
            url = LS.j('data/agree', 'site', 'ek') + '&data=' + oItem.id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oItem.agreed = value;
            });
        }
    };
    $scope.writeItemRemark = function(oItem) {
        var itemRemarks;
        if ($scope.remarks && $scope.remarks.length) {
            itemRemarks = [];
            $scope.remarks.forEach(function(oRemark) {
                if (oRemark.data_id && oRemark.data_id === oItem.id) {
                    itemRemarks.push(oRemark);
                }
            });
        }
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.remarks = itemRemarks;
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            windowClass: 'model-remark',
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/add', 'site', 'ek') + '&data=' + oItem.id, { content: data.content }).then(function(rsp) {
                var oNewRemark;
                oNewRemark = rsp.data;
                oNewRemark.data = oItem;
                oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                $scope.remarks.splice(0, 0, oNewRemark);
                $timeout(function() {
                    var elRemark, parentNode, offsetTop;
                    elRemark = document.querySelector('#remark-' + oNewRemark.id);
                    parentNode = elRemark.parentNode;
                    while (parentNode && parentNode.tagName !== 'BODY') {
                        offsetTop += parentNode.offsetTop;
                        parentNode = parentNode.parentNode;
                    }
                    document.body.scrollTop = offsetTop - 40;
                    elRemark.classList.add('blink');
                    $timeout(function() {
                        elRemark.classList.remove('blink');
                    }, 1000);
                });
            });
        });
    };
    $scope.shareItem = function(oItem) {
        location.href = LS.j('', 'site', 'app', 'ek') + '&data=' + oItem.id + '&page=share';
    };
    $scope.$watch('record', function(oRecord) {
        if (oRecord) {
            $scope.constraint = $scope.ruleCowork(oRecord);
        }
    }, true);
}]);