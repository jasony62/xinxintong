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
        url = LS.j('remark/add', 'site', 'ek', 'schema', 'data');
        if (oRemark) {
            url += '&remark=' + oRemark.id;
        }
        return http2.post(url, { content: content });
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
                url = LS.j('record/recommend', 'site', 'ek');
                url += '&value=' + value;
                http2.get(url).then(function(rsp) {
                    oRecord.agreed = value;
                });
            }
        } else {
            oRecData = $scope.data;
            if (oRecData.agreed !== value) {
                url = LS.j('data/recommend', 'site', 'ek', 'schema');
                url += '&value=' + value;
                http2.get(url).then(function(rsp) {
                    oRecData.agreed = value;
                });
            }
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
            http2.get(LS.j('data/like', 'site', 'ek', 'schema', 'data')).then(function(rsp) {
                oRecData.like_log = rsp.data.like_log;
                oRecData.like_num = rsp.data.like_num;
            });
        }
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
    $scope.bRemarkRecord = !_schemaId; // 评论记录还是数据
    $scope.bRequireOption = true;
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oAssignedSchema;
        oApp = params.app;
        aShareable = [];
        for (var i = 0, ii = oApp.dataSchemas.length; i < ii; i++) {
            if (oApp.dataSchemas[i].shareable && oApp.dataSchemas[i].shareable === 'Y') {
                aShareable.push(oApp.dataSchemas[i]);
            }
            if (oApp.dataSchemas[i].id === LS.s().schema) {
                oAssignedSchema = oApp.dataSchemas[i];
            }
        }
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
                var oRecord;
                $scope.record = oRecord = rsp.data;
                aShareable.forEach(function(oSchema) {
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
                });
                listRemarks();
                /*设置页面分享信息*/
                $scope.setSnsShare(oRecord);
            });
            $scope.visibleSchemas = aShareable;
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
                    if (oRecord.userid !== $scope.user.uid) {
                        $scope.bRequireOption = false;
                    }
                    $scope.record = oRecord;
                    $scope.data = oRecData;
                    listRemarks();
                    /*设置页面分享信息*/
                    $scope.setSnsShare(oRecord, { 'schema': LS.s().schema, 'data': LS.s().data });
                }
            });
            $scope.visibleSchemas = [oAssignedSchema];
        }
    });
}]);