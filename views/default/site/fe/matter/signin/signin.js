'use strict';
require('./signin.css');
var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['http2', '$q', '$timeout', 'tmsLocation', function(http2, $q, $timeout, LS) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, oItem, oSchema, value, sCheckResult;
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = 0, ii = dataSchemas.length; i < ii; i++) {
                oItem = dataSchemas[i];
                oSchema = oItem.schema;
                //定义value
                if (oSchema.id.indexOf('member.') === 0) {
                    var memberSchema = oSchema.id.substr(7);
                    if (memberSchema.indexOf('.') === -1) {
                        value = data.member[memberSchema];
                    } else {
                        memberSchema = memberSchema.split('.');
                        value = data.member.extattr[memberSchema[1]];
                    }
                } else {
                    value = data[oSchema.id];
                }
                /* 为了兼容老版本 */
                if (oSchema.required === undefined && oItem.config.required === 'Y') {
                    oSchema.required = 'Y';
                }
                if (oSchema.type && oSchema.type !== 'html') {
                    if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(oSchema, value))) {
                        return sCheckResult;
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(data, ek) {
        var url, d, posted;
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = LS.j('record/submit', 'site', 'app');
        ek && ek.length && (url += '&ek=' + ek);
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function(d2) {
                    delete d2.imgSrc;
                });
            }
        }
        return http2.post(url, posted, { autoBreak: false });
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
ngApp.directive('tmsImageInput', ['$compile', '$q', function($compile, $q) {
    var modifiedImgFields, openPickFrom, onSubmit;
    modifiedImgFields = [];
    openPickFrom = function(scope) {
        var html;
        html = "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'camera')\">拍照</button></div>";
        html += "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'album')\">相册</button></div>";
        html = __util.makeDialog('pickImageFrom', {
            body: html
        });
        $compile(html)(scope);
    };
    onSubmit = function(data) {
        var defer = $q.defer(),
            i = 0,
            j = 0,
            nextWxImage;
        defer.resolve('ok');
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', 'noticebox', function($scope, $timeout, noticebox) {
            $scope.beforeSubmit(function() {
                return onSubmit($scope.data);
            });
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count) {
                        noticebox.warn('最多允许上传' + count + '张图片');
                        return;
                    }
                }
                if (window.YixinJSBridge) {
                    if (from === undefined) {
                        $scope.cachedImgFieldName = imgFieldName;
                        openPickFrom($scope);
                        return;
                    }
                    imgFieldName = $scope.cachedImgFieldName;
                    $scope.cachedImgFieldName = null;
                    angular.element('#pickImageFrom').remove();
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase, i, j, img;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                        });
                    }
                    $timeout(function() {
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            //if (window.wx !== undefined) {
                            document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').setAttribute('src', img.imgSrc);
                            //}
                        }
                        $scope.$broadcast('xxt.signin.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(imgField, index) {
                imgField.splice(index, 1);
            };
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', function($q) {
    var r, onSubmit;
    //require(['resumable'], function(Resumable) {
    r = new Resumable({
        target: '/rest/site/fe/matter/signin/record/uploadFile?site=' + LS.s().site + '&aid=' + LS.s().app,
        testChunks: false,
        chunkSize: 512 * 1024
    });
    //});
    onSubmit = function($scope) {
        var defer;
        defer = $q.defer();
        if (!r.files || r.files.length === 0)
            defer.resolve('empty');
        r.on('progress', function() {
            var phase, p;
            p = r.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        r.on('complete', function() {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = '完成';
                });
            }
            r.cancel();
            defer.resolve('ok');
        });
        r.upload();
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', function($scope) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            $scope.chooseFile = function(fileFieldName, count, accept) {
                var ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f;
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        r.addFile(f);
                        $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                        $scope.data[fileFieldName].push({
                            uniqueIdentifier: r.files[0].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    }
                    $scope.$apply('data.' + fileFieldName);
                    $scope.$broadcast('xxt.signin.file.choose.done', fileFieldName);
                }, false);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlSignin', ['$scope', 'Input', 'tmsLocation', 'noticebox', function($scope, Input, LS, noticebox) {
    function doSubmit(nextAction) {
        var ek, btnSubmit;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit($scope.data, ek).then(function(rsp) {
            var url;
            if (rsp.data.forword) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + rsp.data.forword;
                url += '&ek=' + rsp.data.ek;
                location.replace(url);
                noticebox.success('完成提交');
            } else if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
                noticebox.success('完成提交');
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data.ek;
                location.replace(url);
                noticebox.success('完成提交');
            } else {
                noticebox.success('完成提交');
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data.ek
                    }
                }
                $scope.$broadcast('xxt.app.signin.submit.done', rsp.data);
            }
        }, function(rsp) {
            submitState.finish();
        });
    }

    function doTask(seq, nextAction) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
        });
    }
    window.onbeforeunload = function() {
        // 保存未提交数据
        submitState.modified && submitState.cache($scope.data);
    };

    var facInput, submitState, tasksOfBeforeSubmit;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {}
    };
    $scope.supplement = {};
    $scope.submitState = submitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.submit = function(event, nextAction) {
        var sCheckResult, cacheKey, oApp, oRecord;
        oApp = $scope.app;
        oRecord = $scope.record;
        if (!submitState.isRunning()) {
            cacheKey = '/site/' + oApp.siteid + '/app/signin/' + oApp.id + '/record/' + (oRecord ? oRecord.enroll_key : '') + '/submit';
            submitState.start(event, cacheKey);
            if (true === (sCheckResult = facInput.check($scope.data, oApp, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                submitState.finish();
                noticebox.warn(sCheckResult);
            }
        }
    };
    $scope.doAction = function(event, oAction) {
        switch (oAction.name) {
            case 'submit':
                $scope.submit(event, oAction.next);
                break;
            case 'gotoPage':
                $scope.gotoPage(event, oAction.next);
                break;
            case 'closeWindow':
                $scope.closeWindow();
                break;
        }
    };
    $scope.$on('xxt.app.signin.ready', function(event, params) {
        if (params.record) {
            ngApp.oUtilSchema.loadRecord(params.app._schemasById, $scope.data, params.record.data);
            $scope.record = params.record;
        }
        /* 恢复用户未提交的数据 */
        if (window.localStorage) {
            var cached = submitState.fromCache();
            if (cached) {
                if (cached.member) {
                    delete cached.member;
                }
                angular.extend($scope.data, cached);
                submitState.modified = true;
            }
        }
        // 跟踪数据变化
        $scope.$watch('data', function(nv, ov) {
            if (nv !== ov) {
                submitState.modified = true;
            }
        }, true);
    });
    var hasAutoFillMember = false;
    $scope.$watch('data.member.schema_id', function(schemaId) {
        if (false === hasAutoFillMember && schemaId && $scope.user) {
            ngApp.oUtilSchema.autoFillMember($scope.user, $scope.data.member);
            hasAutoFillMember = true;
        }
    });
}]);