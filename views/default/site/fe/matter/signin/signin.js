'use strict';
require('./signin.css');
var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['$http', '$q', '$timeout', 'ls', function($http, $q, $timeout, LS) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, item, schema, value, sCheckResult;
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = 0, ii = dataSchemas.length; i < ii; i++) {
                item = dataSchemas[i];
                schema = item.schema;
                //定义value
                if (schema.id.indexOf('member.') === 0) {
                    var memberSchema = schema.id.substr(7);
                    if (memberSchema.indexOf('.') === -1) {
                        value = data.member[memberSchema];
                    } else {
                        memberSchema = memberSchema.split('.');
                        value = data.member.extattr[memberSchema[1]];
                    }
                } else {
                    value = data[schema.id];
                }
                if (item.config.required === 'Y') {
                    schema.required = 'Y';
                }
                if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(schema, value))) {
                    return sCheckResult;
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(data, ek) {
        var defer, url, d, d2, posted;
        defer = $q.defer();
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = '/rest/site/fe/matter/signin/record/submit?site=' + LS.p.site + '&app=' + LS.p.app;
        ek && ek.length && (url += '&ek=' + ek);
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                for (var j in d) {
                    d2 = d[j];
                    delete d2.imgSrc;
                }
            }
        }
        $http.post(url, posted).success(function(rsp) {
            if (typeof rsp === 'string') {
                defer.reject(rsp);
            } else if (rsp.err_code != 0) {
                defer.reject(rsp.err_msg);
                return rsp.err_msg;
            } else {
                defer.resolve(rsp);
            }
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmPopup');
                el.onload = function() {
                    this.height = document.querySelector('body').clientHeight;
                };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function() {
                        el.style.display = 'none';
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
                defer.notify({ code: httpCode, content: content });
            } else {
                defer.reject({ code: httpCode, content: content });
            }
        });
        return defer.promise;
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
        // if (window.wx !== undefined && modifiedImgFields.length) {
        //     nextWxImage = function() {
        //         var imgField, img;
        //         imgField = data[modifiedImgFields[i]];
        //         img = imgField[j];
        //         window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
        //             if (j < imgField.length - 1) {
        //                 /* next img*/
        //                 j++;
        //                 nextWxImage();
        //             } else if (i < modifiedImgFields.length - 1) {
        //                 /* next field*/
        //                 j = 0;
        //                 i++;
        //                 nextWxImage();
        //             } else {
        //                 defer.resolve('ok');
        //             }
        //         });
        //     };
        //     nextWxImage();
        // } else {
        defer.resolve('ok');
        //}
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            $scope.beforeSubmit(function() {
                return onSubmit($scope.data);
            });
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count) {
                        $scope.$parent.errmsg = '最多允许上传' + count + '张图片';
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
        target: '/rest/site/fe/matter/signin/record/uploadFile?site=' + LS.p.site + '&aid=' + LS.p.app,
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
ngApp.controller('ctrlSignin', ['$scope', '$http', 'Input', 'ls', function($scope, $http, Input, LS) {
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
                $scope.$parent.errmsg = '完成提交';
            } else if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
                $scope.$parent.errmsg = '完成提交';
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data.ek;
                location.replace(url);
                $scope.$parent.errmsg = '完成提交';
            } else {
                $scope.$parent.errmsg = '完成提交';
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data.ek
                    }
                }
                $scope.$broadcast('xxt.app.signin.submit.done', rsp.data);
            }
        }, function(rsp) {
            if (rsp && typeof rsp === 'string') {
                $scope.$parent.errmsg = rsp;
                return;
            }
            if (rsp && rsp.err_msg) {
                $scope.$parent.errmsg = rsp.err_msg;
                submitState.finish();
                return;
            }
            $scope.$parent.errmsg = '网络异常，提交失败';
            submitState.finish();
        }, function(rsp) {});
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
        submitState.modified && submitState.cache();
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
                $scope.$parent.errmsg = sCheckResult;
            }
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
