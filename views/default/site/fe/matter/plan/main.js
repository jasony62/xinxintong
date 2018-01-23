'use strict';
require('./main.css');
require('../../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.date.js');
require('../../../../../../asset/js/xxt.ui.share.js');
require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

require('../enroll/directive.css');
require('../enroll/directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'ngRoute', 'ui.bootstrap', 'directive.enroll', 'notice.ui.xxt', 'http.ui.xxt', 'date.ui.xxt', 'snsshare.ui.xxt']);
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.factory('Input', ['$q', '$timeout', 'http2', 'tmsLocation', function($q, $timeout, http2, LS) {
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
        url = '/rest/site/fe/matter/plan/task/submit?site=' + LS.s().site + '&task=' + oTask.id;
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
ngApp.directive('tmsFileInput', ['$q', 'tmsLocation', function($q, LS) {
    function onSubmit($scope) {
        var defer;

        defer = $q.defer();
        if (!oResumable.files || oResumable.files.length === 0) {
            defer.resolve('empty');
        }
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
        target: '/rest/site/fe/matter/plan/task/uploadFile?site=' + LS.s().site + '&app=' + LS.s().app,
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
                    $scope.data[oAction.id] === undefined && ($scope.data[oAction.id] = {});
                    $scope.data[oAction.id][oSchema.id] === undefined && ($scope.data[oAction.id][oSchema.id] = []);
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        oResumable.addFile(f);
                        $scope.data[oAction.id][oSchema.id].push({
                            uniqueIdentifier: oResumable.files[oResumable.files.length - 1].uniqueIdentifier,
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
ngApp.config(['$compileProvider', '$routeProvider', '$locationProvider', 'tmsLocationProvider', function($compileProvider, $routeProvider, $locationProvider, tmsLocationProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
    var RouteParam = function(name) {
        this.templateUrl = name + '.html';
        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
        this.reloadOnSearch = false;
    };
    $routeProvider
        .when('/rest/site/fe/matter/plan/task', new RouteParam('task'))
        .when('/rest/site/fe/matter/plan/rank', new RouteParam('rank'))
        .otherwise(new RouteParam('plan'));
    $locationProvider.html5Mode(true);
    tmsLocationProvider.config('/rest/site/fe/matter/plan');
}]);
/**
 * 计划任务活动
 */
ngApp.controller('ctrlMain', ['$scope', '$location', 'http2', 'tmsLocation', 'tmsSnsShare', function($scope, $location, http2, LS, tmsSnsShare) {
    var _oApp, _oUser;
    $scope.subView = '';
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'plan' ? 'plan' : subView[1];
    });
    $scope.toggleView = function(view, obj) {
        var oSearch = angular.copy($location.search());
        delete oSearch.task;
        switch (view) {
            case 'rank':
                $location.path('/rest/site/fe/matter/plan/rank').search(oSearch);
                break;
            case 'task':
                oSearch.task = obj.id;
                $location.path('/rest/site/fe/matter/plan/task').search(oSearch);
                break;
            default:
                $location.path('/rest/site/fe/matter/plan').search(oSearch);
        }
    };
    $scope.siteUser = function() {
        var url;
        url = '/rest/site/fe/user';
        url += "?site=" + LS.s().site;
        location.href = url;
    };
    $scope.invite = function() {
        if (!_oUser.loginExpire) {
            tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                _oUser.loginExpire = data.loginExpire;
                location.href = "/rest/site/fe/invite?matter=plan," + _oApp.id;
            });
        } else {
            location.href = "/rest/site/fe/invite?matter=plan," + _oApp.id;
        }
    };
    http2.get(LS.j('get', 'site', 'app')).then(function(rsp) {
        $scope.app = _oApp = rsp.data.app;
        $scope.user = _oUser = rsp.data.user;

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
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    });
}]);
/**
 * 任务列表
 */
ngApp.controller('ctrlPlan', ['$scope', '$filter', 'http2', 'tmsLocation', function($scope, $filter, http2, LS) {
    function getUserPlan() {
        http2.get(LS.j('overview', 'site', 'app')).then(function(rsp) {
            $scope.overview = _oOverview = rsp.data;
            http2.get(LS.j('task/listByUser', 'site', 'app')).then(function(rsp) {
                var userTasks, mockTasks;
                userTasks = rsp.data.tasks;
                mockTasks = rsp.data.mocks;
                userTasks.forEach(function(oTask) {
                    oTask.bornAt = $filter('tmsDate')(oTask.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    if (_oApp._taskSchemasById[oTask.task_schema_id]) {
                        _oApp._taskSchemasById[oTask.task_schema_id].userTask = oTask;
                    }
                });
                if (mockTasks) {
                    mockTasks.forEach(function(oMock) {
                        oMock.bornAt = $filter('tmsDate')(oMock.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                        if (_oApp._taskSchemasById[oMock.id]) {
                            _oApp._taskSchemasById[oMock.id].mockTask = oMock;
                        }
                    });
                }
                _oApp.tasks.forEach(function(oTaskSchema) {
                    if (oTaskSchema.as_placeholder === 'N' && !oTaskSchema.userTask && oTaskSchema.mockTask) {
                        if (_oOverview.nowTaskSchema && oTaskSchema.task_seq < _oOverview.nowTaskSchema.task_seq) {
                            oTaskSchema.isDelayed = 'Y';
                        } else if (_oOverview.lastUserTask && oTaskSchema.task_seq < _oOverview.lastUserTask.task_seq) {
                            oTaskSchema.isDelayed = 'Y';
                        }
                    }
                });
            });
        });
    }
    var _oApp, _oOverview;
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        http2.post(LS.j('config', 'site', 'app'), { 'start_at': data.value }).then(function(rsp) {
            getUserPlan();
        });
    });
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        getUserPlan();
    });
}]);
/**
 * 单个任务
 */
ngApp.controller('ctrlTask', ['$scope', '$filter', 'noticebox', 'http2', 'Input', 'tmsLocation', function($scope, $filter, noticebox, http2, Input, LS) {
    function doSubmit() {
        facInput.submit($scope.activeTask, $scope.data, $scope.supplement).then(function(rsp) {
            _oSubmitState.finish();
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

    var facInput, _oSubmitState, tasksOfBeforeSubmit;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {};
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
    http2.get(LS.j('task/get', 'site', 'task')).then(function(rsp) {
        var oTask;
        oTask = rsp.data;
        /* 任务数据 */
        if (oTask.actions) {
            oTask.actions.forEach(function(oAction) {
                if ($scope.app.checkSchemas.length) {
                    var pos = 0;
                    $scope.app.checkSchemas.forEach(function(oSchema) {
                        oAction.checkSchemas.splice(pos++, 0, oSchema);
                    });
                }
                if (oTask.userTask) {
                    var schemasById, oUserTask;
                    oUserTask = oTask.userTask;
                    /* 处理任务时间 */
                    oUserTask.bornAt = $filter('tmsDate')(oUserTask.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    if (oUserTask.patch_at > 0) {
                        oUserTask.patchAt = $filter('tmsDate')(oUserTask.patch_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    }
                    /* 处理任务数据 */
                    if (oUserTask.data[oAction.id]) {
                        schemasById = {};
                        oAction.checkSchemas.forEach(function(oSchema) {
                            schemasById[oSchema.id] = oSchema;
                        });
                        $scope.data[oAction.id] = {};
                        ngApp.oUtilSchema.loadRecord(schemasById, $scope.data[oAction.id], oUserTask.data[oAction.id]);
                    }
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
        $scope.userTask = oTask.userTask;
    });
}]);
/**
 * 排行
 */
ngApp.controller('ctrlRank', ['$scope', 'http2', '$q', 'tmsLocation', function($scope, http2, $q, LS) {
    function list() {
        var defer = $q.defer();
        switch (oAppState.criteria.obj) {
            case 'user':
                http2.post(LS.j('rank/byUser', 'site', 'app'), oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                break;
        }
        return defer.promise;
    }
    $scope.doSearch = function() {
        list().then(function(data) {
            var oSchema;
            switch (oAppState.criteria.obj) {
                case 'user':
                    if (data) {
                        data.forEach(function(user) {
                            $scope.users.push(user);
                        });
                    }
                    break;
            }
        });
    };
    $scope.changeCriteria = function() {
        $scope.users = [];
        $scope.groups = [];
        $scope.doSearch(1);
    };
    var _oApp, oAppState;
    $scope.rankView = {
        obj: 'user'
    };
    if (!oAppState) {
        oAppState = {
            criteria: {
                obj: 'user',
                orderby: 'task_num',
            }
        };
    }
    $scope.appState = oAppState;
    $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
        if (oNew && oOld && oNew !== oOld) {
            switch (oNew) {
                case 'user':
                    oAppState.criteria.orderby = 'task_num';
                    break;
            }
            $scope.changeCriteria();
        }
    });
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        $scope.changeCriteria();
    });
}]);