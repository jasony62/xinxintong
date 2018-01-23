'use strict';
require('./remark.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlRemark', ['$scope', '$timeout', 'tmsLocation', 'http2', '$sce', '$uibModal', function($scope, $timeout, LS, http2, $sce, $uibModal) {
    function listRemarks() {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/list?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        _recDataId && (url += '&data=' + _recDataId);
        return http2.get(url);
    }

    function summary() {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/summary?site=' + oApp.siteid + '&ek=' + ek;
        return http2.get(url);
    }

    function addRemark(content, oRemark) {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/add?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        url += '&id=' + _recDataId;
        if (oRemark) {
            url += '&remark=' + oRemark.id;
        }
        return http2.post(url, { content: content });
    }

    var oApp, aRemarkable, oFilter, ek, schemaId, _recDataId;
    ek = LS.s().ek;
    schemaId = LS.s().schema;
    _recDataId = LS.s().data;
    $scope.newRemark = {};
    $scope.filter = oFilter = {};
    $scope.openOptions = function() {
        $uibModal.open({
            templateUrl: 'options.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.remarkableSchemas = aRemarkable;
                $scope2.data = {};
                $scope2.data.schema = oFilter.schema;
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            oFilter.schema = data.schema;
        });
    };
    $scope.recommend = function(oRecData, value) {
        var url;
        if (oRecData.agreed !== value) {
            url = '/rest/site/fe/matter/enroll/data/recommend';
            url += '?site=' + oApp.siteid;
            url += '&ek=' + $scope.data.enroll_key;
            url += '&schema=' + schemaId;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecData.agreed = value;
            });
        }
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
    $scope.likeRecordData = function() {
        var url;
        url = '/rest/site/fe/matter/enroll/data/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + $scope.data.enroll_key;
        url += '&schema=' + $scope.filter.schema.id;
        url += '&id=' + _recDataId;
        http2.get(url).then(function(rsp) {
            $scope.data.like_log = rsp.data.like_log;
            $scope.data.like_num = rsp.data.like_num;
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
    $scope.gotoRecord = function() {
        var oPage;
        if ($scope.data.userid === $scope.user.uid) {
            for (var i in $scope.app.pages) {
                oPage = $scope.app.pages[i];
                if (oPage.type === 'V') {
                    $scope.gotoPage(null, oPage.name, $scope.data.enroll_key);
                    break;
                }
            }
        }
    };
    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId])) {
            if (val = $scope.data.value) {
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
    $scope.bRequireOption = true;
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oSchema;
        oApp = params.app;
        aRemarkable = [];
        for (var i = 0, ii = oApp.dataSchemas.length; i < ii; i++) {
            if (oApp.dataSchemas[i].remarkable && oApp.dataSchemas[i].remarkable === 'Y') {
                aRemarkable.push(oApp.dataSchemas[i]);
            }
            if (schemaId && oApp.dataSchemas[i].id === schemaId) {
                oSchema = oApp.dataSchemas[i];
            }
        }
        if (oSchema) {
            oFilter.schema = oSchema;
        } else if (aRemarkable.length) {
            oFilter.schema = aRemarkable[0];
        }
        $scope.remarkableSchemas = aRemarkable;
        var groupOthersById;
        if (params.groupOthers && params.groupOthers.length) {
            groupOthersById = {};
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
            $scope.groupOthers = groupOthersById;
        }
        http2.get(LS.j('data/get', 'site', 'ek', 'schema', 'data')).then(function(rsp) {
            var oRecData;
            if (oRecData = rsp.data) {
                if (oFilter.schema.type == 'file' || (oFilter.schema.type == 'multitext' && oRecData.multitext_seq == '0')) {
                    oRecData.value = angular.fromJson(oRecData.value);
                }
                if (oRecData.tag) {
                    oRecData.tag.forEach(function(index, tagId) {
                        if (oApp._tagsById[index]) {
                            oRecData.tag[tagId] = oApp._tagsById[index];
                        }
                    });
                }
                $scope.data = oRecData;
                if (aRemarkable.length <= 1 && oRecData.userid !== $scope.user.uid) {
                    $scope.bRequireOption = false;
                }
            }
        });
    });
    $scope.$watch('filter', function(nv) {
        if (nv && nv.schema) {
            listRemarks().then(function(rsp) {
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
    }, true);
}]);