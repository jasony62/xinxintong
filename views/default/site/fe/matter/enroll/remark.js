'use strict';
require('./remark.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlRemark', ['$scope', '$q', '$timeout', 'http2', '$sce', '$uibModal', function($scope, $q, $timeout, http2, $sce, $uibModal) {
    function listRemarks() {
        var url, defer = $q.defer();
        url = '/rest/site/fe/matter/enroll/remark/list?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        url += '&id=' + itemId;
        http2.get(url).then(function(rsp) {
            var oRecordData;
            if (oRecordData = rsp.data.data) {
                if (oFilter.schema.type == 'file'||oFilter.schema.type == 'multitext') {
                    oRecordData.value = angular.fromJson(oRecordData.value);
                }
            }
            defer.resolve(rsp.data)
        });
        return defer.promise;
    }

    function summary() {
        var url, defer = $q.defer();
        url = '/rest/site/fe/matter/enroll/remark/summary?site=' + oApp.siteid + '&ek=' + ek;
        http2.get(url).then(function(rsp) {
            defer.resolve(rsp.data)
        });
        return defer.promise;
    }

    function addRemark(content, oRemark) {
        var url;
        url = '/rest/site/fe/matter/enroll/remark/add?site=' + oApp.siteid + '&ek=' + ek;
        url += '&schema=' + $scope.filter.schema.id;
        url += '&id=' + itemId;
        if (oRemark) {
            url += '&remark=' + oRemark.id;
        }
        return http2.post(url, { content: content });
    }

    var oApp, aRemarkable, oFilter, ek, schemaId, itemId;
    ek = location.search.match(/[\?&]ek=([^&]*)/)[1];
    if (location.search.match(/[\?&]schema=[^&]*/)) {
        schemaId = location.search.match(/[\?&]schema=([^&]*)/)[1];
    } else {
        schemaId = null;
    }
    if(location.search.match(/[\?&]id=([^&]*)/)) {
        $scope.itemId = itemId = location.search.match(/[\?&]id=([^&]*)/)[1];
    }else {
        $scope.itemId = itemId = null;
    }
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
            url = '/rest/site/fe/matter/enroll/record/recommend';
            url += '?site=' + oApp.siteid;
            url += '&ek=' + $scope.record.enroll_key;
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
        url = '/rest/site/fe/matter/enroll/record/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + $scope.record.enroll_key;
        url += '&schema=' + $scope.filter.schema.id;
        url += '&id=' + itemId;
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
    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId]) && $scope.record.data) {
            if (val = $scope.record.data[schemaId]) {
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
        $scope.record = params.record;
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
        if (aRemarkable.length <= 1 && $scope.record.userid !== $scope.user.uid) {
            $scope.bRequireOption = false;
        }
        var groupOthersById;
        if (params.groupOthers && params.groupOthers.length) {
            groupOthersById = {};
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
            $scope.groupOthers = groupOthersById;
        }
    });
    $scope.$watch('filter', function(nv) {
        if (nv && nv.schema) {
            listRemarks().then(function(data) {
                var oRemark, oUpperRemark, oRemarks = {};
                if (data.remarks && data.remarks.length) {
                    for (var i = data.remarks.length - 1; i >= 0; i--) {
                        oRemark = data.remarks[i];
                        if (oRemark.content) {
                            oRemark.content = oRemark.content.replace(/\n/g, '<br/>');
                        }
                        if (oRemark.remark_id !== '0') {
                            oUpperRemark = oRemarks[oRemark.remark_id];
                            oRemark.content = '<a href="" ng-click="gotoUpper(' + oRemark.remark_id + ')">回复 ' + oUpperRemark.nickname + ' 的评论：</a><br/>' + oRemark.content;
                        }
                        oRemarks[oRemark.id] = oRemark;
                    }
                }
                if (data.data) {
                    if (data.data.tag) {
                        data.data.tag.forEach(function(index, tagId) {
                            if (oApp._tagsById[index]) {
                                data.data.tag[tagId] = oApp._tagsById[index];
                            }
                        });
                    }
                }
                $scope.data = data.data;
                $scope.remarks = data.remarks;
            });
        }
    }, true);
}]);