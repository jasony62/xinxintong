'use strict';
require('./view.css');

var ngApp = require('./main.js');
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
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId]) && facRecord.current && facRecord.current.data) {
            if (val = facRecord.current.data[schemaId]) {
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
        $scope.gotoPage(event, 'remark', facRecord.current.enroll_key);
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
ngApp.controller('ctrlView', ['$scope', 'tmsLocation', 'http2', 'noticebox', 'Record', function($scope, LS, http2, noticebox, Record) {
    function fnGetRecord() {
        return http2.get(LS.j('record/get', 'site', 'app', 'ek'));
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
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp, dataSchemas, facRecord;

        oApp = params.app;
        dataSchemas = params.app.dataSchemas;
        facRecord = Record.ins(oApp);

        fnGetRecord().then(function(rsp) {
            var schemaId, domWrap, aRemarkableSchemas, oRecord;
            facRecord.current = oRecord = rsp.data;
            facRecord.current.tag = facRecord.current.data_tag ? facRecord.current.data_tag : {};
            aRemarkableSchemas = [];
            if (oApp.repos_unit === 'D') {
                dataSchemas.forEach(function(oSchema) {
                    if (oSchema.remarkable && oSchema.remarkable === 'Y') {
                        aRemarkableSchemas.push(oSchema);
                        var domWrap = document.querySelector('[schema=' + oSchema.id + ']');
                        if (domWrap) {
                            domWrap.classList.add('remarkable');
                            domWrap.addEventListener('click', function() {
                                var url = LS.j('', 'site', 'app');
                                url += '&page=remark';
                                url += '&ek=' + oRecord.enroll_key;
                                url += '&schema=' + oSchema.id;
                                url += '&data=' + oRecord.verbose[oSchema.id].id;
                                location.href = url;
                            }, true);
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
        });
    });
}]);