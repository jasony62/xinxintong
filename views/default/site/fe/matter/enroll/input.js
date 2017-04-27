'use strict';
require('./input.css');

require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

var ngApp = require('./main.js');
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
ngApp.factory('Input', ['$http', '$q', '$timeout', 'ls', function($http, $q, $timeout, LS) {
    function required(value, len) {
        return (value == null || value == "" || value.length < len) ? false : true;
    }

    function validateMobile(value) {
        return (false === /^1[3|4|5|7|8][0-9]\d{8}$/.test(value)) ? false : true;
    }

    function validate(data) {
        var reason;
        if (document.querySelector('[ng-model="data.name"]')) {
            reason = '请提供您的姓名！';
            if (false === required(data.name, 2)) {
                document.querySelector('[ng-model="data.name"]').focus();
                return reason;
            }
        }
        if (document.querySelector('[ng-model="data.mobile"]')) {
            reason = '请提供正确的手机号（11位数字）！';
            if (false === validateMobile(data.mobile)) {
                document.querySelector('[ng-model="data.mobile"]').focus();
                return reason;
            }
        }
        return true;
    }

    function isEmpty(schema, value) {
        if (value === undefined) {
            return true;
        }
        switch (schema.type) {
            case 'multiple':
                for (var p in value) {
                    //至少有一个选项
                    if (value[p] === true) {
                        return false;
                    }
                }
                return true;
            default:
                return value.length === 0;
        }
    }

    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var reason, dataSchemas, item, schema, value;
        //验证手机号和姓名规则
        if (true !== (reason = validate(data))) {
            return reason;
        }
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = dataSchemas.length - 1; i >= 0; i--) {
                item = dataSchemas[i];
                schema = item.schema;
                if (item.config.required === 'Y') {
                    if (schema.id.indexOf('member.') === 0) {
                        value = data['member'][schema.id.substr(7)];
                    } else {
                        value = data[schema.id];
                    }
                    if (value === undefined || isEmpty(schema, value)) {
                        return '请填写必填项［' + schema.title + '］';
                    }
                }
                if (schema.number && schema.number === 'Y') {
                    value = data[schema.id];
                    if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
                        return '填写项［' + schema.title + '］只能填写数值';
                    }
                }
                if (/image|file/.test(schema.type)) {
                    if (schema.count) {
                        if (data[schema.id] && data[schema.id].length > schema.count) {
                            return '［' + schema.title + '］超出上传数量（' + schema.count + '）限制';
                        }
                    }
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
        url = LS.j('record/submit', 'site', 'app');
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
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', 'ls', 'tmsDynaPage', function($q, LS, tmsDynaPage) {
    var r, onSubmit;
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function() {
        r = new Resumable({
            target: LS.j('record/uploadFile', 'site', 'app'),
            testChunks: false,
            chunkSize: 512 * 1024
        });
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
                        $scope.$apply(function() {
                            $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                            $scope.data[fileFieldName].push({
                                uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                url: ''
                            });
                            $scope.$broadcast('xxt.enroll.file.choose.done', fileFieldName);
                        });
                    }
                    ele = null;
                }, true);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlInput', ['$scope', '$http', '$q', '$uibModal', 'Input', 'ls', function($scope, $http, $q, $uibModal, Input, LS) {
    var PG = (function() {
        return {
            setMember: function(user, member) {
                var member2, eles;
                if (user && member && member.schema_id && user.members) {
                    if (member2 = user.members[member.schema_id]) {
                        if (angular.isString(member2.extattr)) {
                            if (member2.extattr.length) {
                                member2.extattr = JSON.parse(member2.extattr);
                            } else {
                                member2.extattr = {};
                            }
                        }
                        eles = document.querySelectorAll("[ng-model^='data.member']");
                        angular.forEach(eles, function(ele) {
                            var attr;
                            attr = ele.getAttribute('ng-model');
                            attr = attr.replace('data.member.', '');
                            attr = attr.split('.');
                            if (attr.length == 2) {
                                !member.extattr && (member.extattr = {});
                                member.extattr[attr[1]] = member2.extattr[attr[1]];
                            } else {
                                member[attr[0]] = member2[attr[0]];
                            }
                        });
                    }
                }
            }
        };
    })();
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    window.onbeforeunload = function() {
        // 保存未提交数据
        submitState.modified && submitState.cache();
    };

    function doTask(seq, nextAction) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
        });
    }

    function doSubmit(nextAction) {
        var ek, submitData;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit($scope.data, ek).then(function(rsp) {
            var url;
            submitState.finish();
            if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data;
                location.replace(url);
            } else {
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data
                    }
                }
                $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data);
            }
        }, function(reason) {
            // 如果放开提交状态，有可能导致用户多次提交
            //submitState.finish();
            $scope.$parent.errmsg = reason;
        });
    }
    var facInput, tasksOfBeforeSubmit, submitState;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {},
    };
    $scope.submitState = submitState = {
        modified: false,
        state: 'waiting',
        start: function(event) {
            var submitButton;
            if (event) {
                submitButton = event.target;
                if (submitButton.tagName === 'BUTTON' || ((submitButton = submitButton.parentNode) && submitButton.tagName === 'BUTTON')) {
                    if (/submit\(.*\)/.test(submitButton.getAttribute('ng-click'))) {
                        var span;
                        this.button = submitButton;
                        span = submitButton.querySelector('span');
                        span.setAttribute('data-label', span.innerHTML);
                        span.innerHTML = '正在提交数据...';
                        this.button.classList.add('submit-running');
                    }
                }
            }
            this.state = 'running';
        },
        finish: function() {
            var cacheKey;
            this.state = 'waiting';
            this.modified = false;
            if (this.button) {
                var span;
                span = this.button.querySelector('span');
                span.innerHTML = span.getAttribute('data-label');
                span.removeAttribute('data-label');
                this.button.classList.remove('submit-running');
                this.button = null;
            }
            if (window.localStorage) {
                cacheKey = this._cacheKey();
                window.localStorage.removeItem(cacheKey);
            }
        },
        isRunning: function() {
            return this.state === 'running';
        },
        _cacheKey: function() {
            var app = $scope.app;
            return '/site/' + app.siteid + '/app/' + app.id + '/record/' + ($scope.record ? $scope.record.enroll_key : '') + '/unsubmit';
        },
        cache: function() {
            if (window.localStorage) {
                var key, val;
                key = this._cacheKey();
                val = angular.copy($scope.data);
                val._cacheAt = (new Date() * 1);
                val = JSON.stringify(val);
                window.localStorage.setItem(key, val);
            }
        },
        fromCache: function(keep) {
            if (window.localStorage) {
                var key, val;
                key = this._cacheKey();
                val = window.localStorage.getItem(key);
                if (!keep) window.localStorage.removeItem(key);
                if (val) {
                    val = JSON.parse(val);
                    if (val._cacheAt && (val._cacheAt + 1800000) < (new Date() * 1)) {
                        val = false;
                    }
                    delete val._cacheAt;
                }
            }
            return val;
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var schemasById,
            hasSetMember = false,
            dataOfRecord, p, value;

        $scope.schemasById = schemasById = params.app._schemasById;
        /* 用户已经登记过，恢复之前的数据 */
        if (params.record) {
            dataOfRecord = params.record.data;
            for (p in dataOfRecord) {
                if (p === 'member') {
                    if (angular.isString(dataOfRecord.member)) {
                        dataOfRecord.member = JSON.parse(dataOfRecord.member);
                    }
                    $scope.data.member = angular.extend($scope.data.member, dataOfRecord.member);
                    hasSetMember = true;
                } else if (undefined !== schemasById[p]) {
                    var schema = schemasById[p];
                    if (schema.type === 'score') { // is object
                        $scope.data[p] = dataOfRecord[p];
                    } else if (dataOfRecord[p].length) { // is string
                        if (schema.type === 'image') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = [];
                            for (var i in value) {
                                $scope.data[p].push({
                                    imgSrc: value[i]
                                });
                            }
                        } else if (schema.type === 'file') {
                            value = dataOfRecord[p];
                            $scope.data[p] = value;
                        } else if (schema.type === 'multiple') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = {};
                            for (var i in value) $scope.data[p][value[i]] = true;
                        } else {
                            $scope.data[p] = dataOfRecord[p];
                        }
                    }
                }
            }
            $scope.record = params.record;
        }
        /* 恢复用户未提交的数据 */
        if (window.localStorage) {
            var cached = submitState.fromCache();
            if (cached) {
                angular.extend($scope.data, cached);
                submitState.modified = true;
            }
        }
        // 无论是否有登记记录都自动填写自定义用户信息
        !hasSetMember && PG.setMember(params.user, $scope.data.member);
        // 跟踪数据变化
        $scope.$watch('data', function(nv, ov) {
            if (nv !== ov) {
                submitState.modified = true;
            }
        }, true);
    });
    $scope.submit = function(event, nextAction) {
        var checkResult;
        if (!submitState.isRunning()) {
            submitState.start(event);
            if (true === (checkResult = facInput.check($scope.data, $scope.app, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                submitState.finish();
                $scope.$parent.errmsg = checkResult;
            }
        }
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress($http, $q.defer(), LS.p.site).then(function(data) {
            if (data.errmsg === 'ok') {
                $scope.data[prop] = data.address;
            } else {
                $scope.$parent.errmsg = data.errmsg;
            }
        });
    };
    $scope.dataBySchema = function(schemaId) {
        var app = $scope.app;
        $uibModal.open({
            templateUrl: 'dataBySchema.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close($scope2.data); };
                $http.get('/rest/site/fe/matter/enroll/repos/dataBySchema?site=' + app.siteid + '&app=' + app.id + '&schema=' + schemaId).success(function(result) {
                    $scope2.records = result.data.records;
                });
            }],
            windowClass: 'auto-height',
            backdrop: 'static',
        }).result.then(function(result) {
            $scope.data[schemaId] = result.selected.value;
        });
    };
    $scope.score = function(schemaId, opIndex, number) {
        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            $scope.data[schemaId] = {};
            schema.ops.forEach(function(op) {
                $scope.data[schema.id][op.v] = 0;
            });
        }

        $scope.data[schemaId][op.v] = number;
    };
    $scope.lessScore = function(schemaId, opIndex, number) {
        if (!$scope.schemasById) return false;

        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            return false;
        }

        return $scope.data[schemaId][op.v] >= number;
    };
    $scope.$watch('data.member.authid', function(nv) {
        if (nv && nv.length) PG.setMember($scope.params, $scope.data.member);
    });
}]);
