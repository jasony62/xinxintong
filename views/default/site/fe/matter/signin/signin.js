define(["require", "angular", "angular-sanitize", "xxt-share", "xxt-image", "xxt-geo", "enroll-directive", "enroll-common"], function(require, angular) {
    'use strict';
    ngApp.config(['$compileProvider', function($compileProvider) {
        $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
    }]);
    ngApp.factory('Record', ['$http', '$q', function($http, $q) {
        var Record, _ins;
        Record = function() {};
        Record.prototype.get = function(ek) {
            var url, deferred;
            deferred = $q.defer();
            url = LS.j('record/get', 'site', 'aid');
            ek && (url += '&ek=' + ek);
            $http.get(url).success(function(rsp) {
                if (rsp.err_code == 0) {
                    deferred.resolve(rsp.data);
                }
            });
            return deferred.promise;
        };
        return {
            ins: function() {
                return _ins ? _ins : (new Record());
            }
        };
    }]);
    ngApp.factory('Input', function($http, $q, $timeout) {
        var Input, _ins;
        var required = function(value, len) {
            return (value == null || value == "" || value.length < len) ? false : true;
        };
        var validatePhone = function(value) {
            return (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) ? false : true;
        };
        var validate = function(data) {
            var reason;
            if (document.querySelector('[ng-model="data.name"]') === 1) {
                reason = '请提供您的姓名！';
                if (false === required(data.name, 2)) {
                    document.querySelector('[ng-model="data.name"]').focus();
                    return reason;
                }
            }
            if (document.querySelector('[ng-model="data.mobile"]') === 1) {
                reason = '请提供正确的手机号（11位数字）！';
                if (false === validatePhone(data.mobile)) {
                    document.querySelector('[ng-model="data.mobile"]').focus();
                    return reason;
                }
            }
            return true;
        };
        Input = function() {};
        Input.prototype.check = function(data) {
            var reason;
            reason = validate(data);
            if (true !== reason) {
                return reason;
            }
            if (document.querySelectorAll('.ng-invalid-required').length) {
                return '请填写必填项';
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
                    defer.notify(httpCode);
                } else {
                    defer.reject(content);
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
    });
    ngApp.directive('tmsImageInput', function($compile, $q) {
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
            if (window.wx !== undefined && modifiedImgFields.length) {
                nextWxImage = function() {
                    var imgField, img;
                    imgField = data[modifiedImgFields[i]];
                    img = imgField[j];
                    window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
                        if (j < imgField.length - 1) {
                            /* next img*/
                            j++;
                            nextWxImage();
                        } else if (i < modifiedImgFields.length - 1) {
                            /* next field*/
                            j = 0;
                            i++;
                            nextWxImage();
                        } else {
                            defer.resolve('ok');
                        }
                    });
                };
                nextWxImage();
            } else {
                defer.resolve('ok');
            }
            return defer.promise;
        };
        return {
            restrict: 'A',
            controller: function($scope, $timeout) {
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
                                if (window.wx !== undefined) {
                                    document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').setAttribute('src', img.imgSrc);
                                }
                            }
                            $scope.$broadcast('xxt.enroll.image.choose.done', imgFieldName);
                        });
                    });
                };
                $scope.removeImage = function(imgField, index) {
                    imgField.splice(index, 1);
                };
            }
        }
    });
    ngApp.directive('tmsFileInput', function($q) {
        var r, onSubmit;
        r = new Resumable({
            target: '/rest/site/fe/matter/signin/record/uploadFile?site=' + LS.p.site + '&aid=' + LS.p.aid,
            testChunks: false,
            chunkSize: 512 * 1024
        });
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
            controller: function($scope) {
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
                        $scope.$broadcast('xxt.enroll.file.choose.done', fileFieldName);
                    }, false);
                    ele.click();
                };
            }
        }
    });
    ngApp.controller('ctrlSignin', ['$scope', '$http', 'Input', function($scope, $http, Input) {
        var facInput, tasksOfOnReady, tasksOfBeforeSubmit;
        tasksOfBeforeSubmit = [];
        facInput = Input.ins();
        $scope.data = {
            member: {}
        };
        $scope.beforeSubmit = function(fn) {
            if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
                tasksOfBeforeSubmit.push(fn);
            }
        };
        $scope.$on('xxt.app.signin.ready', function(event, params) {
            if (params.record) {
                var schemas = params.app.data_schemas,
                    mapSchema = {},
                    dataOfRecord = params.record.data,
                    p, value;
                angular.forEach(schemas, function(def) {
                    mapSchema[def.id] = def;
                });
                dataOfRecord = params.record.data;
                for (p in dataOfRecord) {
                    if (p === 'member') {
                        $scope.data.member = angular.extend($scope.data.member, dataOfRecord.member);
                    } else if (dataOfRecord[p].length) {
                        if (mapSchema[p].type === 'img') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = [];
                            for (var i in value) $scope.data[p].push({
                                imgSrc: value[i]
                            });
                        } else if (mapSchema[p].type === 'file') {
                            value = JSON.parse(dataOfRecord[p]);
                            $scope.data[p] = value;
                        } else if (mapSchema[p].type === 'multiple') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = {};
                            for (var i in value) $scope.data[p][value[i]] = true;
                        } else {
                            $scope.data[p] = dataOfRecord[p];
                        }
                    }
                }
                $scope.record = params.record;
            }
            /* 无论是否有登记记录都自动填写用户认证信息 */
            PG.setMember(params.user, $scope.data.member);
        });
        var doSubmit = function(nextAction) {
            var ek, btnSubmit;
            btnSubmit = document.querySelector('#btnSubmit');
            btnSubmit && btnSubmit.setAttribute('disabled', true);
            ek = $scope.record ? $scope.record.enroll_key : undefined;
            facInput.submit($scope.data, ek).then(function(rsp) {
                var url;
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    url = LS.j('', 'site', 'app');
                    url += '&ek=' + rsp.data;
                    url += '&page=' + nextAction;
                    location.replace(url);
                } else {
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                    if (ek === undefined) {
                        $scope.record = {
                            enroll_key: rsp.data
                        }
                    }
                    $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data);
                }
            }, function(reason) {
                btnSubmit && btnSubmit.removeAttribute('disabled');
                $scope.$parent.errmsg = reason;
            });
        };
        var doTask = function(seq, nextAction) {
            var task = tasksOfBeforeSubmit[seq];
            task().then(function(rsp) {
                seq++;
                seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
            });
        };
        $scope.submit = function(event, nextAction) {
            var checkResult, task, seq;
            if (true === (checkResult = facInput.check($scope.data))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                $scope.$parent.errmsg = checkResult;
            }
        };
        $scope.$watch('data.member.authid', function(nv) {
            if (nv && nv.length) PG.setMember($scope.params, $scope.data.member);
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});