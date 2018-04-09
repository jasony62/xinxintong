'use strict';
require('./view.css');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.factory('Record', ['http2', 'tmsLocation', function(http2, LS) {
    var Record, _ins, _deferredRecord;
    Record = function(oApp) {
        var data = {}; // 初始化空数据，优化加载体验
        oApp.dataSchemas.forEach(function(schema) {
            data[schema.id] = '';
        });
        this.current = {
            enroll_at: 0,
            data: data
        };
    };
    Record.prototype.remove = function(record) {
        var url;
        url = LS.j('record/remove', 'site', 'app');
        url += '&ek=' + record.enroll_key;
        return http2.get(url);
    };
    return {
        ins: function(oApp) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecord', ['$scope', 'Record', 'tmsLocation', '$sce', 'noticebox', function($scope, Record, LS, $sce, noticebox) {
    var facRecord;

    $scope.value2Label = function(schemaId) {
        var oData, attr, val;
        if (schemaId && facRecord.current && facRecord.current.data) {
            oData = facRecord.current.data;
            attr = schemaId.split('.');
            if (attr.length === 1) {
                val = oData[schemaId];
            } else if (oData.member) {
                if (attr.length === 2) {
                    val = oData.member[attr[1]];
                } else if (attr.length === 3 && oData.member.extattr) {
                    val = oData.member.extattr[attr[2]];
                }
            }
        }
        return $sce.trustAsHtml(val);
    };
    $scope.score2Html = function(schemaId) {
        var label = '',
            schema = $scope.app._schemasById[schemaId],
            val;

        if (schema && facRecord.current && facRecord.current.data && facRecord.current.data[schemaId]) {
            val = facRecord.current.data[schemaId];
            if (schema.ops && schema.ops.length) {
                schema.ops.forEach(function(op, index) {
                    label += '<div>' + op.l + ': ' + (val[op.v] ? val[op.v] : 0) + '</div>';
                });
            }
        }
        return $sce.trustAsHtml(label);
    };
    $scope.editRecord = function(event, page) {
        if ($scope.app.can_cowork && $scope.app.can_cowork !== 'Y') {
            if ($scope.user.uid !== facRecord.current.userid) {
                noticebox.warn('不允许修改他人提交的数据');
                return;
            }
        }
        if (!page) {
            for (var i in $scope.app.pages) {
                var oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    page = oPage.name;
                    break;
                }
            }
        }
        $scope.gotoPage(event, page, facRecord.current.enroll_key);
    };
    $scope.remarkRecord = function(event) {
        $scope.gotoPage(event, 'cowork', facRecord.current.enroll_key);
    };
    $scope.removeRecord = function(event, page) {
        if ($scope.app.can_cowork && $scope.app.can_cowork !== 'Y') {
            if ($scope.user.uid !== facRecord.current.userid) {
                noticebox.warn('不允许删除他人提交的数据');
                return;
            }
        }
        facRecord.remove(facRecord.current).then(function(data) {
            page && $scope.gotoPage(event, page);
        });
    };
    $scope.$watch('app', function(app) {
        if (!app) return;
        $scope.Record = facRecord = Record.ins(app);
    });
}]);
ngApp.controller('ctrlView', ['$scope', '$sce', 'tmsLocation', 'http2', 'noticebox', 'Record', function($scope, $sce, LS, http2, noticebox, Record) {
    function fnGetRecord() {
        return http2.get(LS.j('record/get', 'site', 'app', 'ek', 'rid'));
    }

    function fnProcessData(oData) {
        var originalValue, afterValue, aProcessing;
        $scope.app.dataSchemas.forEach(function(oSchema) {
            if (oSchema.schema_id && oData.member) {
                var attr;
                attr = oSchema.id.split('.');
                if (attr.length === 2) {
                    originalValue = oData.member[attr[1]];
                    aProcessing = [oData.member, attr[1]];
                } else if (attr.length === 3) {
                    originalValue = oData.member.extattr[attr[2]];
                    if (originalValue && oSchema.type === 'multiple') {
                        var originalValue2 = [];
                        angular.forEach(originalValue, function(v, k) {
                            if (v) originalValue2.push(k);
                        });
                        originalValue = originalValue2;
                    }
                    aProcessing = [oData.member.extattr, attr[2]];
                }
            } else {
                originalValue = oData[oSchema.id];
                if (originalValue) {
                    switch (oSchema.type) {
                        case 'multiple':
                            originalValue = originalValue.split(',');
                            break;
                        case 'url':
                            originalValue._text = ngApp.oUtilSchema.urlSubstitute(originalValue);
                            break;
                    }
                }
                aProcessing = [oData, oSchema.id];
            }
            if (originalValue) {
                switch (oSchema.type) {
                    case 'longtext':
                        afterValue = ngApp.oUtilSchema.txtSubstitute(originalValue);
                        break;
                    case 'single':
                        if (oSchema.ops && oSchema.ops.length) {
                            for (var i = oSchema.ops.length - 1; i >= 0; i--) {
                                if (originalValue === oSchema.ops[i].v) {
                                    afterValue = oSchema.ops[i].l;
                                }
                            }
                        }
                        break;
                    case 'multiple':
                        if (oSchema.ops && oSchema.ops.length) {
                            afterValue = [];
                            oSchema.ops.forEach(function(op) {
                                originalValue.indexOf(op.v) !== -1 && afterValue.push(op.l);
                            });
                            afterValue = afterValue.join(',');
                        }
                        break;
                    default:
                        afterValue = originalValue;
                }
            }
            aProcessing[0][aProcessing[1]] = afterValue || originalValue || (/image|multitext/.test(oSchema.type) ? '' : '[空]');
            afterValue = undefined;
        });
    }

    function fnDisableActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('button[ng-click]')) {
            if (domActs.forEach) {
                domActs.forEach(function(domAct) {
                    var ngClick = domAct.getAttribute('ng-click');
                    if (ngClick.indexOf('editRecord') === 0 || ngClick.indexOf('removeRecord') === 0) {
                        domAct.style.display = 'none';
                    }
                });
            }
        }
    }

    /**
     * 控制关联题目的可见性
     */
    function fnToggleAssocSchemas(dataSchemas, oRecordData) {
        dataSchemas.forEach(function(oSchema) {
            var domSchema;
            domSchema = document.querySelector('[wrap=value][schema="' + oSchema.id + '"]');
            if (domSchema && oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                var bVisible, oRule;
                bVisible = true;
                for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
                    oRule = oSchema.visibility.rules[i];
                    if (oRule.schema.indexOf('member.extattr') === 0) {
                        var memberSchemaId = oRule.schema.substr(15);
                        if (!oRecordData.member.extattr[memberSchemaId] || (oRecordData.member.extattr[memberSchemaId] !== oRule.op && !oRecordData.member.extattr[memberSchemaId][oRule.op])) {
                            bVisible = false;
                            break;
                        }
                    } else if (!oRecordData[oRule.schema] || (oRecordData[oRule.schema] !== oRule.op && !oRecordData[oRule.schema][oRule.op])) {
                        bVisible = false;
                        break;
                    }
                }
                domSchema.classList.toggle('hide', !bVisible);
                oSchema.visibility.visible = bVisible;
            }
        });
    }

    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp, dataSchemas, facRecord;

        oApp = params.app;
        dataSchemas = params.app.dataSchemas;
        facRecord = Record.ins(oApp);

        fnGetRecord().then(function(rsp) {
            var schemaId, domWrap, aRemarkableSchemas, oRecord, oOriginalData;
            oOriginalData = angular.copy(rsp.data.data);
            /* 设置题目的可见性 */
            fnToggleAssocSchemas(dataSchemas, oOriginalData);
            /* 将数据转换为可直接显示的形式 */
            fnProcessData(rsp.data.data);
            facRecord.current = oRecord = rsp.data;
            facRecord.current.tag = facRecord.current.data_tag ? facRecord.current.data_tag : {};
            aRemarkableSchemas = [];
            if (oApp.repos_unit === 'D') {
                dataSchemas.forEach(function(oSchema) {
                    if (oSchema.remarkable && oSchema.remarkable === 'Y') {
                        if (oRecord.verbose[oSchema.id]) {
                            aRemarkableSchemas.push(oSchema);
                            var domWrap = document.querySelector('[schema=' + oSchema.id + ']');
                            if (domWrap) {
                                domWrap.classList.add('remarkable');
                                domWrap.addEventListener('click', function() {
                                    var url = LS.j('', 'site', 'app');
                                    url += '&page=cowork';
                                    url += '&ek=' + oRecord.enroll_key;
                                    url += '&schema=' + oSchema.id;
                                    url += '&data=' + oRecord.verbose[oSchema.id].id;
                                    location.href = url;
                                }, true);
                            }
                        }
                    }
                });
                if (oRecord.verbose) {
                    aRemarkableSchemas.forEach(function(oSchema) {
                        var num;
                        if (domWrap = document.querySelector('[schema=' + oSchema.id + ']')) {
                            num = oRecord.verbose[oSchema.id] ? oRecord.verbose[oSchema.id].remark_num : 0;
                            domWrap.setAttribute('data-remark', num);
                        }
                    });
                }
            }
            /* disable actions */
            if (oApp.end_submit_at > 0 && parseInt(oApp.end_submit_at) < (new Date * 1) / 1000) {
                fnDisableActions();
            } else if ((oApp.can_cowork && oApp.can_cowork !== 'Y')) {
                if (params.user.uid !== oRecord.userid) {
                    fnDisableActions();
                }
            }
            /*设置页面分享信息*/
            $scope.setSnsShare(oRecord);
            /*设置页面导航*/
            var oAppNavs = {};
            if (oApp.can_repos === 'Y') {
                oAppNavs.repos = {};
            }
            if (oApp.can_rank === 'Y') {
                oAppNavs.rank = {};
            }
            if (oApp.scenarioConfig && oApp.scenarioConfig.can_action === 'Y') {
                oAppNavs.action = {};
            }
            if (Object.keys(oAppNavs).length) {
                $scope.appNavs = oAppNavs;
            }
        });
    });
}]);