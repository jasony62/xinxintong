'use strict';
require('./input.css');

require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['$q', '$timeout', 'tmsLocation', 'http2', function($q, $timeout, LS, http2) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, item, oSchema, value, sCheckResult;
        if (page.dataSchemas && page.dataSchemas.length) {
            dataSchemas = page.dataSchemas;
            for (var i = dataSchemas.length - 1; i >= 0; i--) {
                item = dataSchemas[i];
                oSchema = item.schema;
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
                if (oSchema.type && oSchema.type !== 'html') {
                    if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(oSchema, value))) {
                        return sCheckResult;
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(ek, data, tags, oSupplement, type) {
        var url, d, posted, tagsByScchema;
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = LS.j('record/submit', 'site', 'app', 'rid');
        ek && (url += '&ek=' + ek);
        url += type == 'save' ? '&subType=save' : '&subType=submit';
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function(d2) {
                    delete d2.imgSrc;
                });
            }
        }
        tagsByScchema = {};
        if (Object.keys && Object.keys(tags).length > 0) {
            for (var schemaId in tags) {
                tagsByScchema[schemaId] = [];
                tags[schemaId].forEach(function(oTag) {
                    tagsByScchema[schemaId].push(oTag.id);
                });
            }
        }
        return http2.post(url, { data: posted, tag: tags, supplement: oSupplement }, { autoBreak: false });
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
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', 'noticebox', function($scope, $timeout, noticebox) {
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    aModifiedImgFields.indexOf(imgFieldName) === -1 && aModifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count && count != 0) {
                        noticebox.warn('最多允许上传（' + count + '）张图片');
                        return;
                    }
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
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
ngApp.directive('tmsFileInput', ['$q', 'tmsLocation', 'tmsDynaPage', function($q, LS, tmsDynaPage) {
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
    }
    var oResumable;
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function() {
        oResumable = new Resumable({
            target: LS.j('record/uploadFile', 'site', 'app'),
            testChunks: false,
            chunkSize: 512 * 1024
        });
    });
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
                        oResumable.addFile(f);
                        $scope.$apply(function() {
                            $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                            $scope.data[fileFieldName].push({
                                uniqueIdentifier: oResumable.files[oResumable.files.length - 1].uniqueIdentifier,
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
ngApp.controller('ctrlInput', ['$scope', '$q', '$uibModal', '$timeout', 'Input', 'tmsLocation', 'http2', 'noticebox', function($scope, $q, $uibModal, $timeout, Input, LS, http2, noticebox) {
    function fnDisableActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('button[ng-click]')) {
            domActs.forEach(function(domAct) {
                var ngClick = domAct.getAttribute('ng-click');
                if (ngClick.indexOf('submit') === 0) {
                    domAct.style.display = 'none';
                }
            });
        }
    }
    /**
     * 控制题目关联选项的显示
     */
    function fnToggleAssocOptions(dataSchemas, oRecordData) {
        dataSchemas.forEach(function(oSchemaWrap) {
            var oSchema, oConfig;
            if ((oConfig = oSchemaWrap.config) && (oSchema = oSchemaWrap.schema)) {
                if (oSchema.ops && oSchema.ops.length && oSchema.optGroups && oSchema.optGroups.length) {
                    oSchema.optGroups.forEach(function(oOptGroup) {
                        if (oOptGroup.assocOp && oOptGroup.assocOp.schemaId && oOptGroup.assocOp.v) {
                            if (oRecordData[oOptGroup.assocOp.schemaId] !== oOptGroup.assocOp.v) {
                                oSchema.ops.forEach(function(oOption) {
                                    var domOption;
                                    if (oOption.g && oOption.g === oOptGroup.i) {
                                        if (oSchema.type === 'single' && oConfig.component === 'S') {
                                            domOption = document.querySelector('option[name="data.' + oSchema.id + '"][value=' + oOption.v + ']');
                                            if (domOption && domOption.parentNode) {
                                                domOption.parentNode.removeChild(domOption);
                                            }
                                        } else {
                                            if (oSchema.type === 'single') {
                                                domOption = document.querySelector('input[name=' + oSchema.id + '][value=' + oOption.v + ']');
                                            } else if (oSchema.type === 'multiple') {
                                                domOption = document.querySelector('input[ng-model="data.' + oSchema.id + '.' + oOption.v + '"]');
                                            }
                                            if (domOption && (domOption = domOption.parentNode) && (domOption = domOption.parentNode)) {
                                                domOption.classList.add('option-hide');
                                            }
                                        }
                                        if (oSchema.type === 'single') {
                                            if (oRecordData[oSchema.id] === oOption.v) {
                                                oRecordData[oSchema.id] = '';
                                            }
                                        } else {
                                            if (oRecordData[oSchema.id] && oRecordData[oSchema.id][oOption.v]) {
                                                delete oRecordData[oSchema.id][oOption.v];
                                            }
                                        }
                                    }
                                });
                            } else {
                                oSchema.ops.forEach(function(oOption) {
                                    var domOption, domSelect;
                                    if (oOption.g && oOption.g === oOptGroup.i) {
                                        if (oSchema.type === 'single' && oConfig.component === 'S') {
                                            domSelect = document.querySelector('select[ng-model="data.' + oSchema.id + '"]');
                                            if (domSelect) {
                                                domOption = domSelect.querySelector('option[name="data.' + oSchema.id + '"][value=' + oOption.v + ']');
                                                if (!domOption) {
                                                    domOption = document.createElement('option');
                                                    domOption.setAttribute('value', oOption.v);
                                                    domOption.setAttribute('name', 'data.' + oSchema.id);
                                                    domOption.innerHTML = oOption.l;
                                                    domSelect.appendChild(domOption);
                                                }
                                            }
                                        } else {
                                            if (oSchema.type === 'single') {
                                                domOption = document.querySelector('input[name=' + oSchema.id + '][value=' + oOption.v + ']');
                                            } else if (oSchema.type === 'multiple') {
                                                domOption = document.querySelector('input[ng-model="data.' + oSchema.id + '.' + oOption.v + '"]');
                                            }
                                            if (domOption && (domOption = domOption.parentNode) && (domOption = domOption.parentNode)) {
                                                domOption.classList.remove('option-hide');
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                }
            }
        });
    }

    function doTask(seq, nextAction, type) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction, type) : doSubmit(nextAction, type);
        });
    }

    function doSubmit(nextAction, type) {
        var ek, submitData;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit(ek, $scope.data, $scope.tag, $scope.supplement, type).then(function(rsp) {
            var url;
            if (type == 'save') {
                noticebox.success('保存成功，关闭页面后，再次打开时自动恢复当前数据。确认数据填写完成后，请继续【提交】数据。');
            } else {
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
            }

        }, function(rsp) {
            // reject
            submitState.finish();
        });
    }

    function _localSave(type) {
        submitState.start(null, StateCacheKey, type);
        submitState.cache($scope.data);
        submitState.finish(true);
    }

    window.onbeforeunload = function(e) {
        var message;
        if (submitState.modified) {
            message = '已经修改的内容还没有保存，确定离开？';
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };

    var facInput, tasksOfBeforeSubmit, submitState, StateCacheKey;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {},
    };
    $scope.tag = {};
    $scope.supplement = {};
    $scope.submitState = submitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.$on('xxt.app.enroll.save', function() {
        //_localSave('save');
        $scope.submit(event, 'result', 'save');
    });
    $scope.save = function(event, nextAction) {
        //_localSave('save');
        $scope.submit(event, nextAction, 'save');
        $scope.gotoPage(event, nextAction);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var schemasById, dataOfRecord, p, value;
        StateCacheKey = 'xxt.app.enroll:' + params.app.id + '.user:' + params.user.uid + '.cacheKey';
        $scope.schemasById = schemasById = params.app._schemasById;

        if (params.page.data_schemas) {
            params.page.dataSchemas = JSON.parse(params.page.data_schemas);
        }
        // 如果页面上有保存按钮，隐藏内置的保存按钮
        if (params.page.act_schemas) {
            var actSchemas = JSON.parse(params.page.act_schemas);
            for (var i = actSchemas.length - 1; i >= 0; i--) {
                if (actSchemas[i].name === 'save') {
                    var domSave = document.querySelector('.tms-switch-save');
                    if (domSave) {
                        domSave.style.display = 'none';
                    }
                    break;
                }
            }
        }
        if (params.app.end_submit_at > 0 && parseInt(params.app.end_submit_at) < (new Date * 1) / 1000) {
            fnDisableActions();
            noticebox.warn('活动提交数据时间已经结束，不能提交数据');
        }
        /* 判断多项类型 */
        if (params.app.dataSchemas.length) {
            angular.forEach(params.app.dataSchemas, function(dataSchema) {
                if (dataSchema.type == 'multitext') {
                    $scope.data[dataSchema.id] === undefined && ($scope.data[dataSchema.id] = []);
                }
            });
        }
        /* 恢复用户未提交的数据 */
        if (window.localStorage) {
            submitState._cacheKey = StateCacheKey;
            var cached = submitState.fromCache(StateCacheKey);
            if (cached) {
                if (cached.member) {
                    delete cached.member;
                }
                angular.extend($scope.data, cached);
                submitState.modified = true;
            }
        }
        /* 用户已经登记过，恢复之前的数据 */
        if (LS.s().newRecord !== 'Y') {
            http2.get(LS.j('record/get', 'site', 'app', 'ek'), { autoBreak: false, autoNotice: false }).then(function(rsp) {
                var oRecord;
                oRecord = rsp.data;
                ngApp.oUtilSchema.loadRecord(params.app._schemasById, $scope.data, oRecord.data);
                $scope.record = oRecord;
                if (oRecord.data_tag) {
                    $scope.tag = oRecord.data_tag;
                }
            });
        }
        // 跟踪数据变化
        $scope.$watch('data', function(nv, ov) {
            if (nv !== ov) {
                submitState.modified = true;
                fnToggleAssocOptions(params.page.dataSchemas, $scope.data);
            }
        }, true);
        fnToggleAssocOptions(params.page.dataSchemas, $scope.data);
        // 登录提示
        if (!params.user.unionid) {
            //var domTip = document.querySelector('#appLoginTip');
            //var evt = document.createEvent("HTMLEvents");
            //evt.initEvent("show", false, false);
            //domTip.dispatchEvent(evt);
        }
    });
    var hasAutoFillMember = false;
    $scope.$watch('data.member.schema_id', function(schemaId) {
        if (false === hasAutoFillMember && schemaId && $scope.user) {
            ngApp.oUtilSchema.autoFillMember($scope.user, $scope.data.member);
            hasAutoFillMember = true;
        }
    });
    $scope.removeItem = function(items, index) {
        items.splice(index, 1);
    };
    $scope.addItem = function(schemaId) {
        var item = {
            id: 0,
            value: ''
        }
        $scope.data[schemaId].push(item);
    }
    $scope.submit = function(event, nextAction, type) {
        var checkResult;
        /*多项填空题，如果值为空则删掉*/
        for (var k in $scope.data) {
            if (k !== 'member' && $scope.app._schemasById[k] && $scope.app._schemasById[k].type == 'multitext') {
                angular.forEach($scope.data[k], function(item, index) {
                    if (item.value == '') {
                        $scope.data[k].splice(index, 1);
                    }
                });
            }
        }
        if (!submitState.isRunning()) {
            submitState.start(event, StateCacheKey, type);
            if (true === (checkResult = facInput.check($scope.data, $scope.app, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction, type) : doSubmit(nextAction, type);
            } else {
                submitState.finish();
                noticebox.warn(checkResult);
            }
        }
    };
    $scope.tagRecordData = function(schemaId) {
        var oApp, oSchema, tagsOfData;
        oApp = $scope.app;
        oSchema = oApp._schemasById[schemaId];
        if (oSchema) {
            tagsOfData = $scope.tag[schemaId];
            $uibModal.open({
                templateUrl: 'tagRecordData.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var model;
                    $scope2.schema = oSchema;
                    $scope2.apptags = oApp.dataTags;
                    $scope2.model = model = {
                        selected: []
                    };
                    if (tagsOfData) {
                        tagsOfData.forEach(function(oTag) {
                            var index;
                            if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                                model.selected[$scope2.apptags.indexOf(oTag)] = true;
                            }
                        });
                    }
                    $scope2.createTag = function() {
                        var newTags;
                        if ($scope2.model.newtag) {
                            newTags = $scope2.model.newtag.replace(/\s/, ',');
                            newTags = newTags.split(',');
                            http2.post('/rest/site/fe/matter/enroll/tag/create?site=' + $scope.app.siteid + '&app=' + $scope.app.id, newTags).then(function(rsp) {
                                rsp.data.forEach(function(oNewTag) {
                                    $scope2.apptags.push(oNewTag);
                                });
                            });
                            $scope2.model.newtag = '';
                        }
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var tags = [];
                        model.selected.forEach(function(selected, index) {
                            if (selected) {
                                tags.push($scope2.apptags[index]);
                            }
                        });
                        $mi.close(tags);
                    };
                }],
                backdrop: 'static',
            }).result.then(function(tags) {
                $scope.tag[schemaId] = tags;
            });
        }
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress(http2, $q.defer(), LS.p.site).then(function(data) {
            $scope.data[prop] = data.address;
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
                http2.get('/rest/site/fe/matter/enroll/repos/dataBySchema?site=' + app.siteid + '&app=' + app.id + '&schema=' + schemaId).then(function(result) {
                    $scope2.records = result.data.records;
                });
            }],
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
}]);