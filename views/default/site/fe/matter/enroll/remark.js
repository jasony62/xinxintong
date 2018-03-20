'use strict';
require('./remark.css');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.controller('ctrlRemark', ['$scope', '$timeout', '$sce', '$uibModal', 'tmsLocation', 'http2', 'noticebox', function($scope, $timeout, $sce, $uibModal, LS, http2, noticebox) {
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
                    if (oRemark.remark_id !== '0') {
                        oUpperRemark = oRemarks[oRemark.remark_id];
                        oRemark.content = '<a href="" ng-click="gotoUpper(' + oRemark.remark_id + ')">回复 ' + oUpperRemark.nickname + ' 的评论：</a><br/>' + oRemark.content;
                    }
                }
            }
            $scope.remarks = remarks;
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
    /*检查是否满足开始协作条件*/
    function fnCanCowork(oRecord) {
        if (oApp.actionRule) {
            var actionRule;
            actionRule = oApp.actionRule;
            if (actionRule.record && actionRule.record.cowork && actionRule.record.cowork.pre) {
                if (actionRule.record.cowork.pre.record && actionRule.record.cowork.pre.record.likeNum) {
                    if (actionRule.record.cowork.pre.record.likeNum > oRecord.like_num) {
                        if (actionRule.record.cowork.pre.desc) {
                            return actionRule.record.cowork.pre.desc;
                        }
                    }
                }
            }
        }
        return true;
    }

    function fnAfterLoad(oRecord) {
        /*设置任务提示*/
        if (oApp.actionRule) {
            var actionRule, tasks, ruleCheck;
            actionRule = oApp.actionRule;
            tasks = [];
            if (true !== (ruleCheck = fnCanCowork(oRecord))) {
                tasks.push({ type: 'info', msg: ruleCheck, id: 'record.cowork.pre' });
            }
            $scope.tasks = tasks;
        }
        /*设置页面导航*/
        $scope.appNavs = {
            addRecord: {}
        };
        if (oApp.can_repos === 'Y') {
            $scope.appNavs.repos = {};
        }
        if (oApp.can_rank === 'Y') {
            $scope.appNavs.rank = {};
        }
    }

    if (!LS.s().ek) {
        noticebox.error('参数不完整');
        return;
    }
    var oApp, aShareable, ek, _schemaId, _recDataId;
    ek = LS.s().ek;
    _schemaId = LS.s().schema;
    _recDataId = LS.s().data;
    $scope.newRemark = {};
    $scope.recommend = function(value) {
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
    $scope.writeRemark = function(oUpperRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            addRemark(data.content, oUpperRemark).then(function(rsp) {
                var oNewRemark;
                oNewRemark = rsp.data;
                oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                if (oUpperRemark) {
                    oNewRemark.content = '<a href="" ng-click="gotoUpper(' + oUpperRemark.id + ')">回复 ' + oUpperRemark.nickname + ' 的评论：</a><br/>' + oNewRemark.content;
                }
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
    $scope.closeTask = function(index) {
        $scope.tasks.splice(index, 1);
    };
    $scope.bRemarkRecord = !_schemaId; // 评论记录还是数据
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oAssignedSchema, aCoworkSchemas;
        oApp = params.app;
        aShareable = [];
        aCoworkSchemas = [];
        oApp.dataSchemas.forEach(function(oSchema) {
            if (oSchema.shareable && oSchema.shareable === 'Y') {
                aShareable.push(oSchema);
            }
            if ($scope.bRemarkRecord && oSchema.cowork === 'Y') {
                aCoworkSchemas.push(oSchema);
            }
            if (oSchema.id === LS.s().schema) {
                oAssignedSchema = oSchema;
            }
        });
        $scope.coworkSchemas = aCoworkSchemas;
        /**
         * 分组信息
         */
        var groupOthersById;
        if (params.groupOthers && params.groupOthers.length) {
            groupOthersById = {};
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
            $scope.groupOthers = groupOthersById;
        }
        if ($scope.bRemarkRecord) {
            /**
             * 整条记录的评论
             */
            http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function(rsp) {
                var oRecord, aVisibleSchemas;
                $scope.record = oRecord = rsp.data;
                aVisibleSchemas = [];
                aShareable.forEach(function(oSchema) {
                    if (aCoworkSchemas.indexOf(oSchema) === -1) {
                        var oSchemaData;
                        if (oSchemaData = oRecord.verbose[oSchema.id]) {
                            if (!angular.isArray(oSchemaData) || oSchemaData.length) {
                                if (/file|url/.test(oSchema.type)) {
                                    oRecord.verbose[oSchema.id].value = angular.fromJson(oRecord.verbose[oSchema.id].value);
                                    if ('url' === oSchema.type) {
                                        oRecord.verbose[oSchema.id].value._text = ngApp.oUtilSchema.urlSubstitute(oRecord.verbose[oSchema.id].value);
                                    }
                                } else if (oSchema.type === 'image') {
                                    oRecord.verbose[oSchema.id].value = oRecord.verbose[oSchema.id].value.split(',');
                                } else if (oSchema.type === 'single' || oSchema.type === 'multiple') {
                                    oRecord.verbose[oSchema.id].value = $scope.value2Label(oSchema);
                                }
                                aVisibleSchemas.push(oSchema);
                            }
                        }
                    }
                });
                listRemarks();
                /*设置页面分享信息*/
                $scope.setSnsShare(oRecord);
                $scope.visibleSchemas = aVisibleSchemas;
                /* 结束数据加载后的处理 */
                fnAfterLoad(oRecord);
            });
        } else {
            /**
             * 单道题目的评论
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
                        }
                        if (oRecData.tag) {
                            oRecData.tag.forEach(function(index, tagId) {
                                if (oApp._tagsById[index]) {
                                    oRecData.tag[tagId] = oApp._tagsById[index];
                                }
                            });
                        }
                    }
                    $scope.record = oRecord;
                    $scope.data = oRecData;
                    listRemarks();
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
ngApp.controller('ctrlCowork', ['$scope', '$timeout', '$uibModal', 'tmsLocation', 'http2', 'noticebox', function($scope, $timeout, $uibModal, LS, http2, noticebox) {
    $scope.addItem = function(oSchema) {
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
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/add', 'site', 'ek') + '&data=' + oItem.id, { content: data.content }).then(function(rsp) {
                var oNewRemark;
                oNewRemark = rsp.data;
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
    $scope.$watch('record', function(oRecord) {
        if (oRecord) {
            $scope.$watch('coworkSchemas', function(aSchemas) {
                if (aSchemas) {
                    aSchemas.forEach(function(oSchema) {
                        http2.get(LS.j('data/get', 'site', 'ek') + '&schema=' + oSchema.id + '&cascaded=Y', { autoBreak: false, autoNotice: false }).then(function(rsp) {
                            var oRecData;
                            if (rsp.data.verbose && rsp.data.verbose[oSchema.id]) {
                                oRecData = $scope.record.verbose[oSchema.id];
                                oRecData.value = rsp.data.verbose[oSchema.id].items;
                                oRecData.value.forEach(function(oItem) {
                                    if (oItem.userid !== $scope.user.uid) {
                                        oItem._others = true;
                                    }
                                });
                            }
                        }, function() {});
                    });
                }
            });
        }
    });
}]);