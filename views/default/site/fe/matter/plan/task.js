'use strict';
require('./task.css');
require('../../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.share.js');

require('../enroll/directive.css');

require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

require('../enroll/directive.js');

var i18n = {
    weekday: {
        'Mon': '星期一',
        'Tue': '星期二',
        'Wed': '星期三',
        'Thu': '星期四',
        'Fri': '星期五',
        'Sat': '星期六',
        'Sun': '星期日',
    }
};
var ngApp = angular.module('app', ['ngSanitize', 'directive.enroll', 'notice.ui.xxt', 'http.ui.xxt', 'snsshare.ui.xxt']);
ngApp.provider('ls', function() {
    var _baseUrl = '/rest/site/fe/matter/plan',
        _params = {};

    this.params = function(params) {
        var ls;
        ls = location.search;
        angular.forEach(params, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            _params[q] = match ? match[1] : '';
        });
        return _params;
    };

    this.$get = function() {
        return {
            p: _params,
            j: function(method) {
                var i = 1,
                    l = arguments.length,
                    url = _baseUrl,
                    _this = this,
                    search = [];
                method && method.length && (url += '/' + method);
                for (; i < l; i++) {
                    search.push(arguments[i] + '=' + _params[arguments[i]]);
                };
                search.length && (url += '?' + search.join('&'));
                return url;
            }
        };
    };
});
ngApp.config(['$compileProvider', 'lsProvider', function($compileProvider, lsProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
    lsProvider.params(['site', 'app']);
}]);
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.factory('Input', ['$q', '$timeout', 'http2', 'ls', function($q, $timeout, http2, LS) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(oTask, oTaskData) {
        var oAction, oActionData, schemas, oSchema, value, sCheckResult;
        if (oTask.actions && oTask.actions.length) {
            for (var i = 0, ii = oTask.actions.length; i < ii; i++) {
                oAction = oTask.actions[i];
                if (oAction.checkSchemas && oAction.checkSchemas.length) {
                    schemas = oAction.checkSchemas;
                    oActionData = oTaskData[oAction.id];
                    for (var j = 0, jj = schemas.length; j < jj; j++) {
                        oSchema = schemas[j];
                        if (oSchema.type && oSchema.type !== 'html') {
                            value = oActionData ? oActionData[oSchema.id] : '';
                            if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(oSchema, value))) {
                                return sCheckResult;
                            }
                        }
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(oTask, oTaskData, oSupplement) {
        var posted, d, url;
        posted = {
            data: angular.copy(oTaskData),
            supplement: oSupplement
        };
        url = '/rest/site/fe/matter/plan/task/submit?site=' + LS.p.site + '&task=' + oTask.id;
        for (var i in posted.data) {
            d = posted.data[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function(d2) {
                    delete d2.imgSrc;
                });
            }
        }
        return http2.post(url, posted, { autoNotice: false, autoBreak: false });
    };
    return {
        ins: function() {
            if (!_ins) {
                _ins = new Input();
            }
            return _ins;
        }
    }
}]);
/**
 *上传图片
 */
ngApp.directive('tmsImageInput', ['$compile', '$q', 'noticebox', function($compile, $q, noticebox) {
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            $scope.chooseImage = function(oAction, oSchema, from) {
                var imgFieldName, aSchemaImgs, count;
                imgFieldName = oAction.id + '.' + oSchema.id;
                aModifiedImgFields.indexOf(imgFieldName) === -1 && aModifiedImgFields.push(imgFieldName);
                $scope.data[oAction.id] === undefined && ($scope.data[oAction.id] = {});
                $scope.data[oAction.id][oSchema.id] === undefined && ($scope.data[oAction.id][oSchema.id] = []);
                aSchemaImgs = $scope.data[oAction.id][oSchema.id];
                count = parseInt(oSchema.count) || 1;
                if (aSchemaImgs.length === count) {
                    noticebox.warn('最多允许上传（' + count + '）张图片');
                    return;
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[oAction.id][oSchema.id] = aSchemaImgs.concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[oAction.id][oSchema.id] = aSchemaImgs.concat(imgs);
                        });
                    }
                    $timeout(function() {
                        var i, j, img, eleImg;
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            eleImg = document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img');
                            if (eleImg) {
                                eleImg.setAttribute('src', img.imgSrc);
                            }
                        }
                        $scope.$broadcast('xxt.plan.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(oAction, oSchema, index) {
                $scope.data[oAction.id][oSchema.id].splice(index, 1);
            };
        }]
    }
}]);
/**
 * 上传文件
 */
ngApp.directive('tmsFileInput', ['$q', 'ls', function($q, LS) {
    function onSubmit($scope) {
        var defer;
        defer = $q.defer();
        if (!oResumable.files || oResumable.files.length === 0)
            defer.resolve('empty');
        oResumable.on('progress', function() {
            var phase, p;
            p = oResumable.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        oResumable.on('complete', function() {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = '完成';
                });
            }
            oResumable.cancel();
            defer.resolve('ok');
        });
        oResumable.upload();
        return defer.promise;
    };
    var oResumable;
    oResumable = new Resumable({
        target: '/rest/site/fe/matter/plan/task/uploadFile?site=' + LS.p.site + '&app=' + LS.p.app,
        testChunks: false,
        chunkSize: 512 * 1024
    });
    return {
        restrict: 'A',
        controller: ['$scope', function($scope) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            $scope.chooseFile = function(oAction, oSchema, accept) {
                var fileFieldName, ele;
                fileFieldName = oAction.id + '.' + oSchema.id;
                ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f;
                    $scope.data[oAction.id][oSchema.id] === undefined && ($scope.data[oAction.id][oSchema.id] = []);
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        oResumable.addFile(f);
                        $scope.data[oAction.id][oSchema.id].push({
                            uniqueIdentifier: oResumable.files[0].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    }
                    $scope.$apply('data', function() {
                        $scope.$broadcast('xxt.plan.file.choose.done', fileFieldName);
                    });
                }, false);
                ele.click();
            };
        }]
    }
}]);
/**
 * 计划任务活动
 */
ngApp.controller('ctrlMain', ['$scope', '$timeout', '$filter', 'http2', 'ls', 'tmsSnsShare', function($scope, $timeout, $filter, http2, LS, tmsSnsShare) {
    var _oApp;
    $scope.subView = 'plan';
    $scope.toggleView = function(view) {
        $scope.subView = view;
    };
    $scope.$on('xxt.app.plan.submit.done', function() {
        /* 提交任务数据后，有可能更改当前任务 */
        http2.get(LS.j('nowTask', 'site', 'app')).then(function(rsp) {
            _oApp.nowTaskSchema = rsp.data;
        });
    });
    http2.get(LS.j('get', 'site', 'app')).then(function(rsp) {
        $scope.app = _oApp = rsp.data.app;

        _oApp._taskSchemasById = {};
        _oApp.tasks.forEach(function(oTaskSchema) {
            _oApp._taskSchemasById[oTaskSchema.id] = oTaskSchema;
        });

        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            tmsSnsShare.config({
                siteId: _oApp.siteid,
                logger: function(shareto) {},
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
            });
            tmsSnsShare.set(_oApp.title, _oApp.entryUrl, _oApp.summary, _oApp.pic);
        }
        /* 已经执行的任务 */
        http2.get(LS.j('task/listByUser', 'site', 'app')).then(function(rsp) {
            var userTasks, mockTasks;
            userTasks = rsp.data.tasks;
            mockTasks = rsp.data.mocks;
            userTasks.forEach(function(oTask) {
                oTask.bornAt = $filter('date')(oTask.born_at * 1000, 'yy-MM-dd HH:mm') + ',' + i18n.weekday[$filter('date')(oTask.born_at * 1000, 'EEE')];
                if (_oApp._taskSchemasById[oTask.task_schema_id]) {
                    _oApp._taskSchemasById[oTask.task_schema_id].userTask = oTask;
                }
            });
            mockTasks.forEach(function(oMock) {
                oMock.bornAt = $filter('date')(oMock.born_at * 1000, 'yy-MM-dd HH:mm') + ',' + i18n.weekday[$filter('date')(oMock.born_at * 1000, 'EEE')];
                if (_oApp._taskSchemasById[oMock.id]) {
                    _oApp._taskSchemasById[oMock.id].mockTask = oMock;
                }
            });
            _oApp.tasks.forEach(function(oTaskSchema) {
                if (oTaskSchema.as_placeholder === 'N' && !oTaskSchema.userTask && oTaskSchema.mockTask) {
                    if (_oApp.nowTaskSchema && oTaskSchema.task_seq < _oApp.nowTaskSchema.task_seq) {
                        oTaskSchema.isDelayed = 'Y';
                    } else if (_oApp.lastUserTask && oTaskSchema.task_seq < _oApp.lastUserTask.task_seq) {
                        oTaskSchema.isDelayed = 'Y';
                    }
                }
            });
            var eleLoading;
            if (eleLoading = document.querySelector('.loading')) {
                eleLoading.parentNode.removeChild(eleLoading);
            }
        });
    });
}]);
/**
 * 计划任务
 */
ngApp.controller('ctrlTask', ['$scope', '$filter', 'noticebox', 'http2', 'Input', 'ls', function($scope, $filter, noticebox, http2, Input, LS) {
    function doSubmit() {
        facInput.submit($scope.activeTask, $scope.data, $scope.supplement).then(function(rsp) {
            _oSubmitState.finish();
            _oToggledTask.userTask = rsp.data;
            delete _oToggledTask.mockTask;
            noticebox.success('完成提交');
            $scope.$emit('xxt.app.plan.submit.done', rsp.data);
        }, function(rsp) {
            _oSubmitState.finish();
            if (rsp && typeof rsp === 'string') {
                noticebox.error(rsp);
                return;
            }
            if (rsp && rsp.err_msg) {
                noticebox.error(rsp.err_msg);
                return;
            }
            noticebox.error('网络异常，提交失败');
        }, function(rsp) {
            _oSubmitState.finish();
        });
    }

    function doTask(seq) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq) : doSubmit();
        });
    }

    window.onbeforeunload = function() {
        // 保存未提交数据
        _oSubmitState.modified && _oSubmitState.cache($scope.data);
    };

    var _oToggledTask, facInput, _oSubmitState, tasksOfBeforeSubmit;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {};
    //$scope.supplement = null;
    $scope._oSubmitState = _oSubmitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.submit = function(event) {
        var sCheckResult;
        if (!_oSubmitState.isRunning()) {
            _oSubmitState.start(event);
            if (true === (sCheckResult = facInput.check($scope.activeTask, $scope.data))) {
                tasksOfBeforeSubmit.length ? doTask(0) : doSubmit();
            } else {
                _oSubmitState.finish();
                noticebox.error(sCheckResult);
            }
        }
    };
    $scope.toggleTask = function(oToggledTask) {
        if (oToggledTask.as_placeholder === 'Y') {
            return;
        }
        _oToggledTask = oToggledTask;
        http2.get(LS.j('task/get', 'site') + '&task=' + oToggledTask.id).then(function(rsp) {
            var oTask;
            oTask = rsp.data;
            /* 任务数据 */
            if (oTask.actions) {
                oTask.actions.forEach(function(oAction) {
                    if ($scope.app.checkSchemas.length) {
                        $scope.app.checkSchemas.forEach(function(oSchema) {
                            oAction.checkSchemas.splice(0, 0, oSchema);
                        });
                    }
                    if (oTask.userTask && oTask.userTask.data[oAction.id]) {
                        var schemasById;
                        schemasById = {};
                        oAction.checkSchemas.forEach(function(oSchema) {
                            schemasById[oSchema.id] = oSchema;
                        });
                        $scope.data[oAction.id] = {};
                        ngApp.oUtilSchema.loadRecord(schemasById, $scope.data[oAction.id], oTask.userTask.data[oAction.id]);
                        oTask.userTask.bornAt = $filter('date')(oTask.userTask.born_at * 1000, 'yy-MM-dd HH:mm') + ',' + i18n.weekday[$filter('date')(oTask.userTask.born_at * 1000, 'EEE')]
                    }
                });
            }
            // 数据补充说明
            if (oTask.userTask && oTask.userTask.supplement) {
                $scope.supplement = oTask.userTask.supplement;
            } else {
                $scope.supplement = {};
            }
            $scope.activeTask = oTask;
            $scope.subView = 'task';
        });
    };
    $scope.closeTask = function() {
        _oToggledTask = null;
        $scope.activeTask = null;
        $scope.subView = 'plan';
    };
}]);
ngApp.controller('ctrlRank', ['$scope', 'http2', 'ls', function($scope, http2, LS) {
    function byUser() {
        http2.get(LS.j('rank/byUser', 'site', 'app')).then(function(rsp) {
            $scope.users = rsp.data;
        });
    }

    function byGroup() {
        http2.get(LS.j('rank/byGroup', 'site', 'app')).then(function(rsp) {});
    }
    var _oApp;
    $scope.rankView = {
        obj: 'user'
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        byUser();
    });
}]);