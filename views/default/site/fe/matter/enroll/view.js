'use strict';
require('./view.css');

var ngApp = require('./main.js');
ngApp.factory('Record', ['http2', '$q', 'ls', function(http2, $q, LS) {
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
    Record.prototype.get = function(ek) {
        var _this, url;
        if (!_deferredRecord) {
            _deferredRecord = $q.defer();
            _this = this;
            url = LS.j('record/get', 'site', 'app');
            ek && (url += '&ek=' + ek);
            http2.get(url).then(function(rsp) {
                var record;
                record = rsp.data;
                _this.current = record;
                _deferredRecord.resolve(record);
            });
        }
        return _deferredRecord.promise;
    };
    Record.prototype.remove = function(record) {
        var deferred = $q.defer(),
            url;
        url = LS.j('record/remove', 'site', 'app');
        url += '&ek=' + record.enroll_key;
        http2.get(url).then(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
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
ngApp.controller('ctrlRecord', ['$scope', 'Record', 'ls', '$sce', function($scope, Record, LS, $sce) {
    var facRecord;

    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId]) && facRecord.current.data) {
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

        if (schema && facRecord.current.data && facRecord.current.data[schemaId]) {
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
                alert('不允许修改他人提交的数据');
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
                alert('不允许删除他人提交的数据');
                return;
            }
        }
        facRecord.remove(facRecord.current).then(function(data) {
            page && $scope.gotoPage(event, page);
        });
    };
    $scope.$watch('app', function(app) {
        var promise;
        if (!app) return;
        facRecord = Record.ins(app);
        if (promise = facRecord.get(LS.p.ek)) {
            promise.then(function(oRecord) {
                var schemaId, domWrap;
                if (oRecord.verbose) {
                    for (schemaId in oRecord.verbose) {
                        if (domWrap = document.querySelector('[schema=' + schemaId + ']')) {
                            domWrap.setAttribute('data-remark', oRecord.verbose[schemaId].remark_num);
                        }
                    }
                }
            });
        }
        $scope.Record = facRecord;
    });
}]);
ngApp.controller('ctrlView', ['$scope', '$timeout', 'ls', 'Record', function($scope, $timeout, LS, Record) {
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp = params.app;
        if (!params.user.unionid) {
            var domTip = document.querySelector('#appLoginTip');
            var evt = document.createEvent("HTMLEvents");
            evt.initEvent("show", false, false);
            domTip.dispatchEvent(evt);
        }
        var dataSchemas = params.app.dataSchemas;
        dataSchemas.forEach(function(oSchema) {
            if (oSchema.remarkable && oSchema.remarkable === 'Y') {
                var domWrap = document.querySelector('[schema=' + oSchema.id + ']');
                domWrap.classList.add('remarkable');
                domWrap.addEventListener('click', function() {
                    var url = LS.j('', 'site', 'app', 'ek');
                    url += '&schema=' + oSchema.id;
                    url += '&page=remark';
                    location.href = url;
                }, true);
            }
        });
        var promise, facRecord;
        facRecord = Record.ins(oApp);
        if (promise = facRecord.get(LS.p.ek)) {
            promise.then(function(oRecord) {
                /* disable actions */
                var fnDisableActions = function() {
                    var domActs, domAct;
                    if (domActs = document.querySelectorAll('button[ng-click]')) {
                        domActs.forEach(function(domAct) {
                            var ngClick = domAct.getAttribute('ng-click');
                            if (ngClick.indexOf('editRecord') === 0 || ngClick.indexOf('removeRecord') === 0) {
                                domAct.style.display = 'none';
                            }
                        });
                    }
                };
                if (oApp.end_at > 0 && parseInt(oApp.end_at) < (new Date() * 1) / 1000) {
                    fnDisableActions();
                } else if ((oApp.can_cowork && oApp.can_cowork !== 'Y')) {
                    if (params.user.uid !== oRecord.userid) {
                        fnDisableActions();
                    }
                }
            });
        }
    });
}]);
