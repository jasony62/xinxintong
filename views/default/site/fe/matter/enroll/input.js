'use strict';
require('../../../../../../asset/css/buttons.css');
require('./input.css');

require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');
require('../../../../../../asset/js/xxt.ui.url.js');
require('../../../../../../asset/js/xxt.ui.paste.js');
require('../../../../../../asset/js/xxt.ui.editor.js');
require('../../../../../../asset/js/xxt.ui.schema.js');

require('./_asset/ui.round.js');
require('./_asset/ui.task.js');

window.moduleAngularModules = ['round.ui.enroll', 'task.ui.enroll', 'paste.ui.xxt', 'editor.ui.xxt', 'url.ui.xxt', 'schema.ui.xxt'];

var ngApp = require('./main.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.config(['$compileProvider', function ($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['$parse', 'tmsLocation', 'http2', 'tmsSchema', function ($parse, LS, http2, tmsSchema) {
    var Input, _ins;
    Input = function () {};
    Input.prototype.check = function (oRecData, oApp, oPage) {
        var dataSchemas, oSchemaWrap, oSchema, value, sCheckResult;
        if (oPage.dataSchemas && oPage.dataSchemas.length) {
            for (var i = 0; i < oPage.dataSchemas.length; i++) {
                oSchemaWrap = oPage.dataSchemas[i];
                oSchema = oSchemaWrap.schema;
                /* 隐藏题和协作题不做检查 */
                if ((!oSchema.visibility || !oSchema.visibility.rules || oSchema.visibility.rules.length === 0 || oSchema.visibility.visible) && oSchema.cowork !== 'Y' && (oSchema._visible !== false)) {
                    if (oSchema.type && oSchema.type !== 'html') {
                        value = $parse(oSchema.id)(oRecData);
                        sCheckResult = tmsSchema.checkValue(oSchema, value);
                        if (true !== sCheckResult) {
                            return sCheckResult;
                        }
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function (oRecord, oRecData, tags, oSupplement, type, taskId) {
        var url, d, oPosted, tagsByScchema;

        oPosted = angular.copy(oRecData);

        if (oRecord.enroll_key) {
            /* 更新已有填写记录 */
            url = LS.j('record/' + (type || 'submit'), 'site', 'app')
            url += '&ek=' + oRecord.enroll_key;
        } else {
            /* 添加新记录 */
            if (oRecord.round) {
                /* 指定了填写轮次 */
                url = LS.j('record/' + (type || 'submit'), 'site', 'app') + '&rid=' + oRecord.round.rid;
            } else {
                url = LS.j('record/' + (type || 'submit'), 'site', 'app', 'rid');
            }
        }
        /* 所属任务 */
        if (taskId) url += '&task=' + taskId;
        for (var i in oPosted) {
            d = oPosted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function (d2) {
                    delete d2.imgSrc;
                });
            }
        }
        tagsByScchema = {};
        if (Object.keys && Object.keys(tags).length > 0) {
            for (var schemaId in tags) {
                tagsByScchema[schemaId] = [];
                tags[schemaId].forEach(function (oTag) {
                    tagsByScchema[schemaId].push(oTag.id);
                });
            }
        }
        return http2.post(url, {
            data: oPosted,
            tag: tags,
            supplement: oSupplement
        }, {
            autoBreak: false
        });
    };
    return {
        ins: function () {
            if (!_ins) {
                _ins = new Input();
            }
            return _ins;
        }
    }
}]);
ngApp.directive('tmsImageInput', ['$compile', '$q', function ($compile, $q) {
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', 'noticebox', function ($scope, $timeout, noticebox) {
            function imgCount(schemaId, count) {
                if (schemaId !== null) {
                    aModifiedImgFields.indexOf(schemaId) === -1 && aModifiedImgFields.push(schemaId);
                    $scope.data[schemaId] === undefined && ($scope.data[schemaId] = []);
                    if (count) {
                        count = parseInt(count);
                        if (count > 0 && $scope.data[schemaId].length >= count) {
                            noticebox.warn('最多允许上传（' + count + '）张图片');
                            return;
                        }
                    }
                }
            }

            function imgBind(schemaId, imgs) {
                var phase;
                phase = $scope.$root.$$phase;
                if (phase === '$digest' || phase === '$apply') {
                    $scope.data[schemaId] = $scope.data[schemaId].concat(imgs);
                } else {
                    $scope.$apply(function () {
                        $scope.data[schemaId] = $scope.data[schemaId].concat(imgs);
                    });
                }
                $timeout(function () {
                    var i, j, img, eleImg;
                    for (i = 0, j = imgs.length; i < j; i++) {
                        img = imgs[i];
                        eleImg = document.querySelector('ul[name="' + schemaId + '"] li:nth-last-child(3) img');
                        if (eleImg) {
                            eleImg.setAttribute('src', img.imgSrc);
                        }
                    }
                    $scope.$broadcast('xxt.enroll.image.choose.done', schemaId);
                });
            }
            $scope.chooseImage = function (schemaId, count, from) {
                imgCount(schemaId, count, from);
                window.xxt.image.choose($q.defer(), from).then(function (imgs) {
                    imgBind(schemaId, imgs);
                });
            };
            $scope.removeImage = function (imgField, index) {
                imgField.splice(index, 1);
            };
            $scope.pasteImage = function (schemaId, event, count, from) {
                imgCount(schemaId, count, from);
                var targetDiv;
                targetDiv = event.currentTarget.children[event.currentTarget.children.length - 1];
                window.xxt.image.paste(angular.element(targetDiv)[0], $q.defer(), from).then(function (imgs) {
                    imgBind(schemaId, imgs);
                });
            };
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', 'tmsLocation', 'tmsDynaPage', function ($q, LS, tmsDynaPage) {
    function onSubmit($scope) {
        var defer;
        defer = $q.defer();
        if (!oResumable.files || oResumable.files.length === 0) {
            defer.resolve('empty');
        }
        oResumable.on('progress', function () {
            var phase, p;
            p = oResumable.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function () {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        oResumable.on('complete', function () {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function () {
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
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function () {
        oResumable = new Resumable({
            target: LS.j('record/uploadFile', 'site', 'app'),
            testChunks: true,
            chunkSize: 512 * 1024
        });
    });
    return {
        restrict: 'A',
        controller: ['$scope', 'noticebox', function ($scope, noticebox) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function () {
                return onSubmit($scope);
            });
            $scope.clickFile = function (schemaId, index) {
                if ($scope.data[schemaId] && $scope.data[schemaId][index]) {
                    noticebox.confirm('删除文件【' + $scope.data[schemaId][index].name + '】，确定？').then(function () {
                        $scope.data[schemaId].splice(index, 1);
                    });
                }
            };
            $scope.chooseFile = function (schemaId, count, accept) {
                var ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function (evt) {
                    var i, cnt, f;
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        oResumable.addFile(f);
                        $scope.$apply(function () {
                            $scope.data[schemaId] === undefined && ($scope.data[schemaId] = []);
                            $scope.data[schemaId].push({
                                uniqueIdentifier: oResumable.files[oResumable.files.length - 1].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                url: ''
                            });
                            $scope.$broadcast('xxt.enroll.file.choose.done', schemaId);
                        });
                    }
                    ele = null;
                }, true);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlWxUploadFileTip', ['$scope', '$interval', function ($scope, $interval) {
    $scope.domId = '';
    $scope.isIos = /iphone|ipad/i.test(navigator.userAgent);
    $scope.closeTip = function () {
        var domTip = document.querySelector($scope.domId);
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("hide", false, false);
        domTip.dispatchEvent(evt);
    };
}]);
ngApp.directive('tmsVoiceInput', ['$q', 'noticebox', function ($q, noticebox) {
    function doUpload2Wx(oPendingData) {
        var defer;
        defer = $q.defer();
        wx.uploadVoice({
            localId: oPendingData.localId,
            isShowProgressTips: 1,
            success: function (res) {
                oPendingData.serverId = res.serverId;
                delete oPendingData.localId;
                defer.resolve();
            },
            fail: function (res) {
                noticebox.error('录音文件上传失败：' + res.errMsg);
                defer.reject();
            }
        });
        return defer.promise;
    }

    return {
        restrict: 'A',
        controller: ['$scope', '$uibModal', 'noticebox', function ($scope, $uibModal, noticebox) {
            $scope.clickFile = function (schemaId, index) {
                var buttons, oSchemaData;
                if ($scope.data[schemaId] && $scope.data[schemaId][index]) {
                    buttons = [{
                        label: '删除',
                        value: 'delete'
                    }, {
                        label: '取消',
                        value: 'cancel'
                    }];
                    oSchemaData = $scope.data[schemaId];
                    noticebox.confirm('操作录音文件【' + oSchemaData[index].name + '】', buttons).then(function (value) {
                        switch (value) {
                            case 'delete':
                                oSchemaData.splice(index, 1);
                                break;
                        }
                    });
                }
            };
            $scope.startVoice = function (schemaId) {
                var oSchema, oSchemaData;
                if (!window.wx || !wx.startRecord) {
                    noticebox.warn('请在微信中进行录音');
                    return;
                }
                if ($scope.schemasById && $scope.schemasById[schemaId]) {
                    oSchema = $scope.schemasById[schemaId];
                } else if ($scope.app && $scope.app.dynaDataSchemas && $scope.app.dynaDataSchemas.length) {
                    for (var i = $scope.app.dynaDataSchemas.length - 1; i >= 0; i--) {
                        if ($scope.app.dynaDataSchemas[i].id = schemaId) {
                            oSchema = $scope.app.dynaDataSchemas[i];
                            break;
                        }
                    }
                }
                if (!oSchema) {
                    noticebox.warn('数据错误，未找到题目定义');
                    return;
                }
                /* 检查限制条件 */
                $scope.data[oSchema.id] === undefined && ($scope.data[oSchema.id] = []);
                oSchemaData = $scope.data[oSchema.id];
                if (oSchema.count && oSchemaData.length >= oSchema.count) {
                    noticebox.warn('最多允许上传（' + oSchema.count + '）段录音');
                    return;
                }
                $uibModal.open({
                    templateUrl: 'recordVoice.html',
                    controller: ['$scope', '$interval', '$uibModalInstance', function ($scope2, $interval, $mi) {
                        var _oData, _timer;
                        $scope2.data = _oData = {
                            name: '录音' + (oSchemaData.length + 1),
                            time: 0,
                            reset: function () {
                                this.time = 0;
                                delete this.localId;
                            }
                        };
                        $scope2.startRecord = function () {
                            wx.startRecord();
                            _oData.reset();
                            _timer = $interval(function () {
                                _oData.time++;
                            }, 1000);
                            wx.onVoiceRecordEnd({
                                // 录音时间超过一分钟没有停止的时候会执行 complete 回调
                                complete: function (res) {
                                    $scope.$apply(function () {
                                        _oData.localId = res.localId;
                                    });
                                    $interval.cancel(_timer);
                                }
                            });
                        };
                        $scope2.stopRecord = function () {
                            wx.stopRecord({
                                success: function (res) {
                                    $scope.$apply(function () {
                                        _oData.localId = res.localId;
                                    });
                                }
                            });
                            $interval.cancel(_timer);
                        };
                        $scope2.play = function () {
                            wx.playVoice({
                                localId: _oData.localId
                            });
                            wx.onVoicePlayEnd({
                                success: function (res) {
                                    var localId = res.localId;
                                }
                            });
                        };
                        $scope2.pause = function () {
                            wx.pauseVoice({
                                localId: _oData.localId
                            });
                        };
                        $scope2.stop = function () {
                            wx.stopVoice({
                                localId: _oData.localId
                            });
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.ok = function () {
                            $mi.close($scope2.data);
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function (oResult) {
                    var oNewVoice;
                    oNewVoice = {
                        localId: oResult.localId,
                        name: oResult.name,
                        time: oResult.time
                    };
                    if (oResult.localId) {
                        $scope.data[oSchema.id].push(oNewVoice);
                    }
                    /* 记录整体提交时处理文件上传 */
                    $scope.beforeSubmit(function () {
                        return doUpload2Wx(oNewVoice);
                    });
                });
            };
            $scope.playVoice = function () {

            };
        }]
    }
}]);
ngApp.controller('ctrlInput', ['$scope', '$parse', '$q', '$uibModal', '$timeout', 'Input', 'tmsLocation', 'http2', 'noticebox', 'tmsPaste', 'tmsUrl', 'tmsSchema', '$compile', 'enlRound', 'enlTask', function ($scope, $parse, $q, $uibModal, $timeout, Input, LS, http2, noticebox, tmsPaste, tmsUrl, tmsSchema, $compile, enlRound, enlTask) {
    function fnHidePageActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('[wrap=button]')) {
            angular.forEach(domActs, function (domAct) {
                domAct.style.display = 'none';
            });
        }
    }
    /**
     * 控制只读题目
     */
    function fnToggleReadonlySchemas(dataSchemas) {
        dataSchemas.forEach(function (oSchemaWrap) {
            var oSchema, domSchemas;
            if (oSchema = oSchemaWrap.schema) {
                domSchemas = document.querySelectorAll('[wrap=input][schema="' + oSchema.id + '"] input');
                domSchemas.forEach(function (one) {
                    oSchema.readonly === 'Y' ? one.setAttribute('disabled', true) : one.removeAttribute('disabled');
                });
            }
        });
    }
    /**
     * 控制轮次题目的可见性
     */
    function fnToggleRoundSchemas(dataSchemas, oRecordData) {
        dataSchemas.forEach(function (oSchemaWrap) {
            var oSchema, domSchema;
            if (oSchema = oSchemaWrap.schema) {
                domSchema = document.querySelector('[wrap=input][schema="' + oSchema.id + '"],[wrap=html][schema="' + oSchema.id + '"]');
                if (domSchema) {
                    if (oSchema.hideByRoundPurpose && oSchema.hideByRoundPurpose.length) {
                        var bVisible = true;
                        if (oSchema.hideByRoundPurpose.indexOf($scope.record.round.purpose) !== -1) {
                            bVisible = false;
                        }
                        oSchema._visible = bVisible;
                        domSchema.classList.toggle('hide', !bVisible);
                        /* 被隐藏的题目需要清除数据 */
                        if (false === bVisible) {
                            $parse(oSchema.id).assign(oRecordData, undefined);
                        }
                    }
                }
            }
        });
    }
    /**
     * 控制关联题目的可见性
     */
    function fnToggleAssocSchemas(dataSchemas, oRecordData) {
        dataSchemas.forEach(function (oSchemaWrap) {
            var oSchema, domSchema;
            if (oSchema = oSchemaWrap.schema) {
                domSchema = document.querySelector('[wrap=input][schema="' + oSchema.id + '"],[wrap=html][schema="' + oSchema.id + '"]');
                if (domSchema) {
                    if (oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                        var bVisible, oRule, oRuleVal;
                        if (oSchema.visibility.logicOR) {
                            bVisible = false;
                            for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
                                oRule = oSchema.visibility.rules[i];
                                oRuleVal = $parse(oRule.schema)(oRecordData);
                                if (oRuleVal) {
                                    if (oRuleVal === oRule.op || oRuleVal[oRule.op]) {
                                        bVisible = true;
                                        break;
                                    }
                                }
                            }
                        } else {
                            bVisible = true;
                            for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
                                oRule = oSchema.visibility.rules[i];
                                oRuleVal = $parse(oRule.schema)(oRecordData);
                                if (!oRuleVal || (oRuleVal !== oRule.op && !oRuleVal[oRule.op])) {
                                    bVisible = false;
                                    break;
                                }
                            }
                        }
                        domSchema.classList.toggle('hide', !bVisible);
                        oSchema.visibility.visible = bVisible;
                        /* 被隐藏的题目需要清除数据 */
                        if (false === bVisible) {
                            $parse(oSchema.id).assign(oRecordData, undefined);
                        }
                    } else if (oSchema.type === 'multitext' && oSchema.cowork === 'Y') {
                        domSchema.classList.toggle('hide', !bVisible);
                    }
                }
            }
        });
    }
    /**
     * 控制题目关联选项的显示
     */
    function fnToggleAssocOptions(pageDataSchemas, oRecordData) {
        pageDataSchemas.forEach(function (oSchemaWrap) {
            var oSchema, oConfig;
            if ((oConfig = oSchemaWrap.config) && (oSchema = oSchemaWrap.schema)) {
                if (oSchema.ops && oSchema.ops.length && oSchema.optGroups && oSchema.optGroups.length) {
                    oSchema.optGroups.forEach(function (oOptGroup) {
                        if (oOptGroup.assocOp && oOptGroup.assocOp.schemaId && oOptGroup.assocOp.v) {
                            if ($parse(oOptGroup.assocOp.schemaId)(oRecordData) !== oOptGroup.assocOp.v) {
                                oSchema.ops.forEach(function (oOption) {
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
                                oSchema.ops.forEach(function (oOption) {
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
    /**
     * 添加辅助功能
     */
    function fnAssistant(pageDataSchemas) {
        pageDataSchemas.forEach(function (oSchemaWrap) {
            var oSchema, domSchema, domRequireAssist;
            if (oSchema = oSchemaWrap.schema) {
                domSchema = document.querySelector('[wrap=input][schema="' + oSchema.id + '"]');
                if (domSchema) {
                    switch (oSchema.type) {
                        case 'longtext':
                            domRequireAssist = document.querySelector('textarea[ng-model="data.' + oSchema.id + '"]');
                            if (domRequireAssist) {
                                domRequireAssist.addEventListener('paste', function (e) {
                                    var text;
                                    e.preventDefault();
                                    text = e.clipboardData.getData('text/plain');
                                    tmsPaste.onpaste(text, {
                                        filter: {
                                            whiteSpace: oSchema.filterWhiteSpace === 'Y'
                                        }
                                    });
                                });
                            }
                            break;
                    }
                    /* 必填题目 */
                    if (oSchema.required && oSchema.required === 'Y') {
                        domSchema.classList.add('schema-required');
                    }
                }
            }
        });
    }
    /**
     * 给关联选项添加选项nickname
     */
    function fnAppendOpNickname(dataSchemas) {
        dataSchemas.forEach(function (oSchema) {
            var domSchema;
            domSchema = document.querySelector('[wrap=input][schema="' + oSchema.id + '"]');
            if (domSchema && oSchema.dsOps && oSchema.showOpNickname === 'Y') {
                switch (oSchema.type) {
                    case 'multiple':
                        if (oSchema.ops && oSchema.ops.length) {
                            var domOptions;
                            domOptions = document.querySelectorAll('[wrap=input][schema="' + oSchema.id + '"] input[type=checkbox][ng-model]');
                            oSchema.ops.forEach(function (oOp, index) {
                                var domOption, spanNickname;
                                if (domOption = domOptions[index]) {
                                    if (oOp.ds && oOp.ds.nickname) {
                                        domOption = domOption.parentNode;
                                        spanNickname = document.createElement('span');
                                        spanNickname.classList.add('option-nickname');
                                        spanNickname.innerHTML = '[' + oOp.ds.nickname + ']';
                                        domOption.appendChild(spanNickname);
                                    }
                                }
                            });
                        }
                        break;
                }
            }
        });
    }
    /**
     * 给关联选项添加选项nickname
     */
    function fnAppendOpDsLink(dataSchemas) {
        dataSchemas.forEach(function (oSchema) {
            var domSchema;
            domSchema = document.querySelector('[wrap=input][schema="' + oSchema.id + '"]');
            if (domSchema && oSchema.dsOps && oSchema.dsOps.app && oSchema.dsOps.app.id && oSchema.showOpDsLink === 'Y') {
                switch (oSchema.type) {
                    case 'multiple':
                        if (oSchema.ops && oSchema.ops.length) {
                            var domOptions;
                            domOptions = document.querySelectorAll('[wrap=input][schema=' + oSchema.id + '] input[type=checkbox][name=' + oSchema.id + '][ng-model]');
                            oSchema.ops.forEach(function (oOp, index) {
                                var domOption, spanLink;
                                if (domOption = domOptions[index]) {
                                    if (oOp.ds && oOp.ds.ek) {
                                        domOption = domOption.parentNode;
                                        spanLink = document.createElement('span');
                                        spanLink.classList.add('option-link');
                                        spanLink.innerHTML = '[详情]';
                                        spanLink.addEventListener('click', function (e) {
                                            e.preventDefault();
                                            var url
                                            url = LS.j('', 'site');
                                            url += '&app=' + oSchema.dsOps.app.id;
                                            url += '&page=cowork';
                                            url += '&ek=' + oOp.ds.ek;
                                            location.href = url;
                                        });
                                        domOption.appendChild(spanLink);
                                    }
                                }
                            });
                        }
                        break;
                }
            }
        });
    }

    function doTask(seq, nextAction, type) {
        var task = _tasksOfBeforeSubmit[seq];
        task().then(function (rsp) {
            seq++;
            seq < _tasksOfBeforeSubmit.length ? doTask(seq, nextAction, type) : doSubmit(nextAction, type);
        });
    }

    function doSubmit(nextAction, type) {
        _facInput.submit($scope.record, $scope.data, $scope.tag, $scope.supplement, type, $scope.forQuestionTask).then(function (rsp) {
            var url;
            if (type == 'save') {
                noticebox.success('保存成功，关闭页面后，再次打开时自动恢复当前数据。确认数据填写完成后，请继续【提交】数据。');
            } else {
                _oSubmitState.finish();
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction === '_autoForward') {
                    // 根据指定的进入规则自动跳转到对应页面
                    url = LS.j('', 'site', 'app');
                    location.replace(url);
                } else if (nextAction && nextAction.length) {
                    url = LS.j('', 'site', 'app');
                    url += '&page=' + nextAction;
                    url += '&ek=' + rsp.data.enroll_key;
                    location.replace(url);
                } else {
                    if ($scope.record.enroll_key === undefined) {
                        $scope.record = {
                            enroll_key: rsp.data.enroll_key
                        }
                    }
                    $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data);
                }
            }

        }, function (rsp) {
            // reject
            _oSubmitState.finish();
        });
    }

    /* 获得页面编辑的记录 */
    function fnAfterGetRecord(oRecord) {
        /* 同轮次的其他记录 */
        http2.post(LS.j('record/list', 'site', 'app') + '&sketch=Y', {
            record: {
                rid: oRecord.round.rid
            }
        }).then(function (rsp) {
            var records;
            records = rsp.data.records || [];
            $scope.recordsOfRound = {
                records: records,
                page: {
                    size: 1,
                    total: rsp.data.total
                },
                shift: function () {
                    fnGetRecord(records[this.page.at - 1].enroll_key);
                }
            };
            for (var i = 0, l = records.length; i < l; i++) {
                if (records[i].enroll_key === oRecord.enroll_key) {
                    $scope.recordsOfRound.page.at = i + 1;
                    break;
                }
            }
        });
        if (oRecord.round && oRecord.round.end_at > 0) {
            if (oRecord.round.end_at * 1000 < (new Date * 1)) {
                noticebox.warn('活动轮次【' + oRecord.round.title + '】已结束，不能提交、修改、保存或删除填写记录！');
                if (_oPage.actSchemas && _oPage.actSchemas.length) {
                    _oPage.actSchemas.forEach(function (oAct) {
                        if (oAct.name === 'submit') {
                            oAct.disabled = true;
                        }
                    });
                }
            }
        }
        /* 判断多项类型 */
        if (_oApp.dynaDataSchemas.length) {
            angular.forEach(_oApp.dynaDataSchemas, function (oSchema) {
                if (oSchema.type == 'multitext') {
                    $scope.data[oSchema.id] === undefined && ($scope.data[oSchema.id] = []);
                }
            });
        }

        tmsSchema.autoFillMember(_oApp._schemasById, $scope.user, $scope.data.member);

        tmsSchema.loadRecord(_oApp._schemasById, $scope.data, oRecord.data);

        $scope.record = oRecord;
        if (oRecord.supplement) {
            $scope.supplement = oRecord.supplement;
        }
        /*设置页面分享信息*/
        $scope.setSnsShare(oRecord, {
            'newRecord': LS.s().newRecord
        });
        /*根据加载的数据设置页面*/
        fnAfterLoad(_oApp, _oPage, oRecord, $scope.data);
    }

    /* 获得要编辑记录 */
    function fnGetRecord(ek) {
        var urlLoadRecord;
        if (ek) {
            urlLoadRecord = LS.j('record/get', 'site', 'app') + '&ek=' + ek;
        } else {
            if (LS.s().newRecord === 'Y') {
                urlLoadRecord = LS.j('record/get', 'site', 'app', 'rid') + '&loadLast=N';
            } else {
                urlLoadRecord = LS.j('record/get', 'site', 'app', 'rid', 'ek') + '&loadLast=' + _oApp.open_lastroll + '&withSaved=Y';
            }
        }
        $scope.data = {
            member: {}
        };
        http2.get(urlLoadRecord, {
            autoBreak: false,
            autoNotice: false
        }).then(function (rsp) {
            var oRecord;
            oRecord = rsp.data;
            fnAfterGetRecord(oRecord);
        }, function (rsp) {
            if (LS.s().newRecord === 'Y' && LS.s().rid) {
                _facRound.get([LS.s().rid]).then(function (aRounds) {
                    if (aRounds && aRounds.length === 1) {
                        fnAfterGetRecord({
                            round: aRounds[0]
                        });
                    }
                });
            } else {
                fnAfterGetRecord({
                    round: oRound ? oRound : _oApp.appRound
                });
            }
        });
    }

    function fnGetRecordByRound(oRound) {
        var urlLoadRecord;

        urlLoadRecord = LS.j('record/get', 'site', 'app') + '&rid=' + oRound.rid;
        $scope.data = {
            member: {}
        };
        http2.get(urlLoadRecord, {
            autoBreak: false,
            autoNotice: false
        }).then(function (rsp) {
            fnAfterGetRecord(rsp.data);
        }, function (rsp) {
            if (LS.s().newRecord === 'Y' && LS.s().rid) {
                _facRound.get([LS.s().rid]).then(function (aRounds) {
                    if (aRounds && aRounds.length === 1) {
                        fnAfterGetRecord({
                            round: aRounds[0]
                        });
                    }
                });
            } else {
                fnAfterGetRecord({
                    round: oRound ? oRound : _oApp.appRound
                });
            }
        });
    }

    /* 页面和记录数据加载完成 */
    function fnAfterLoad(oApp, oPage, oRecord, oRecordData) {
        var dataSchemas;
        dataSchemas = oPage.dataSchemas;
        // 设置题目的默认值
        tmsSchema.autoFillDefault(_oApp._schemasById, $scope.data);
        // 控制题目是否只读
        fnToggleReadonlySchemas(dataSchemas);
        // 控制题目的轮次可见性
        fnToggleRoundSchemas(dataSchemas);
        // 控制关联题目的可见性
        fnToggleAssocSchemas(dataSchemas, oRecordData);
        // 控制题目关联选项的可见性
        fnToggleAssocOptions(dataSchemas, oRecordData);
        // 添加辅助功能
        fnAssistant(dataSchemas);
        // 从其他活动生成的选项的昵称
        fnAppendOpNickname(oApp.dynaDataSchemas);
        // 从其他活动生成的选项的详情链接
        fnAppendOpDsLink(oApp.dynaDataSchemas);
        // 跟踪数据变化
        $scope.$watch('data', function (nv, ov) {
            if (nv !== ov) {
                _oSubmitState.modified = true;
                // 控制关联题目的可见性
                fnToggleAssocSchemas(dataSchemas, oRecordData);
                // 控制题目关联选项的可见性
                fnToggleAssocOptions(dataSchemas, oRecordData);
            }
        }, true);
    }

    window.onbeforeunload = function (e) {
        var message;
        if (_oSubmitState.modified) {
            message = '已经修改的内容还没有保存，确定离开？';
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };

    var _facInput, _tasksOfBeforeSubmit, _oSubmitState, _oApp, _oPage, _StateCacheKey, _tkRound;

    _tasksOfBeforeSubmit = [];
    _facInput = Input.ins();
    $scope.tag = {};
    $scope.supplement = {};
    $scope.submitState = _oSubmitState = ngApp.oUtilSubmit.state;

    $scope.beforeSubmit = function (fn) {
        if (_tasksOfBeforeSubmit.indexOf(fn) === -1) {
            _tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.gotoHome = function () {
        location.href = "/rest/site/fe/matter/enroll?site=" + _oApp.siteid + "&app=" + _oApp.id + "&page=repos";
    };
    $scope.removeItem = function (items, index) {
        noticebox.confirm('删除此项，确定？').then(function () {
            items.splice(index, 1);
        });
    };
    $scope.addItem = function (schemaId) {
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.data = {
                    content: ''
                };
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({
                            content: content
                        });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (data) {
            var item = {
                id: 0,
                value: ''
            };
            item.value = data.content;
            if (!$scope.data[schemaId] || !angular.isArray($scope.data[schemaId])) {
                $scope.data[schemaId] = [];
            }
            $scope.data[schemaId].push(item);
        });
    };
    $scope.editItem = function (schema, index) {
        var oItem = schema[index];
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.data = {
                    content: oItem.value
                };
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({
                            content: content
                        });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (data) {
            oItem.value = data.content;
        });
    }
    $scope.submit = function (event, nextAction, type) {
        var checkResult;
        /*多项填空题，如果值为空则删掉*/
        for (var k in $scope.data) {
            if (k !== 'member' && $scope.app._schemasById[k] && $scope.app._schemasById[k].type == 'multitext') {
                angular.forEach($scope.data[k], function (item, index) {
                    if (item.value === '') {
                        $scope.data[k].splice(index, 1);
                    }
                });
            }
        }
        if (!_oSubmitState.isRunning()) {
            _oSubmitState.start(event, _StateCacheKey, type);
            if ($scope.record.round.purpose !== 'C' || type === 'save' || true === (checkResult = _facInput.check($scope.data, $scope.app, $scope.page))) {
                _tasksOfBeforeSubmit.length ? doTask(0, nextAction, type) : doSubmit(nextAction, type);
            } else {
                _oSubmitState.finish();
                noticebox.warn(checkResult);
            }
        }
    };
    $scope.getMyLocation = function (prop) {
        window.xxt.geo.getAddress(http2, $q.defer(), LS.p.site).then(function (data) {
            $scope.data[prop] = data.address;
        });
    };
    $scope.pasteUrl = function (schemaId) {
        tmsUrl.fetch($scope.data[schemaId], {
            description: true,
            text: true
        }).then(function (oResult) {
            var oData;
            oData = angular.copy(oResult.summary);
            oData._text = oResult.text;
            $scope.data[schemaId] = oData;
        });
    };
    $scope.editSupplement = function (schemaId) {
        var str = $scope.supplement[schemaId];
        if (!str) {
            str = '';
        }
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                $scope2.data = {
                    content: str
                };
                $scope2.cancel = function () {
                    $mi.dismiss();
                };
                $scope2.ok = function () {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({
                            content: content
                        });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function (data) {
            $scope.supplement[schemaId] = data.content;
        });
    }
    /**
     * 填写历史数据
     */
    $scope.dataBySchema = function (schemaId) {
        var oRecordData, oHandleSchema, url;
        url = '/rest/site/fe/matter/enroll/repos/dataBySchema?site=' + _oApp.siteid + '&app=' + _oApp.id;
        if (oHandleSchema = $scope.schemasById[schemaId]) {
            oRecordData = $scope.data;
            $uibModal.open({
                templateUrl: 'dataBySchema.html',
                controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                    var oAssocData = {};
                    var oPage;
                    $scope2.page = oPage = {};
                    $scope2.data = {};
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        $mi.close($scope2.data);
                    };
                    $scope2.search = function () {
                        http2.post(url + '&schema=' + oHandleSchema.id, oAssocData, {
                            page: oPage
                        }).then(function (oResult) {
                            var oData = oResult.data[oHandleSchema.id];
                            if (oHandleSchema.type == 'multitext') {
                                oData.records.pop();
                            }
                            $scope2.records = oData.records;
                        });
                    };
                    if (oHandleSchema.history === 'Y' && oHandleSchema.historyAssoc && oHandleSchema.historyAssoc.length) {
                        oHandleSchema.historyAssoc.forEach(function (assocSchemaId) {
                            if (oRecordData[assocSchemaId]) {
                                oAssocData[assocSchemaId] = oRecordData[assocSchemaId];
                            }
                        });
                    }
                    $scope2.search();
                }],
                backdrop: 'static',
            }).result.then(function (oResult) {
                var assocSchemaIds = [];
                if (oResult.selected.value) {
                    $scope.data[oHandleSchema.id] = oResult.selected.value;
                    /* 检查是否存在关联题目，自动完成数据填写 */
                    _oApp.dynaDataSchemas.forEach(function (oOther) {
                        if (oOther.id !== oHandleSchema.id && oOther.history === 'Y' && oOther.historyAssoc && oOther.historyAssoc.indexOf(oHandleSchema.id) !== -1) {
                            assocSchemaIds.push(oOther.id);
                        }
                    });
                    if (assocSchemaIds.length) {
                        var oPosted = {};
                        oPosted[oHandleSchema.id] = $scope.data[oHandleSchema.id];
                        http2.post(url + '&schema=' + assocSchemaIds.join(','), oPosted).then(function (rsp) {
                            for (var schemaId in rsp.data) {
                                if (rsp.data[schemaId].records && rsp.data[schemaId].records.length) {
                                    $scope.data[schemaId] = rsp.data[schemaId].records[0].value;
                                }
                            }
                        });
                    }
                }
            });
        }
    };
    $scope.score = function (schemaId, opIndex, number) {
        var oSchema, oOption;

        if (!(oSchema = $scope.schemasById[schemaId])) return;
        if (!(oOption = oSchema.ops[opIndex])) return;

        if ($scope.data[oSchema.id] === undefined) {
            $scope.data[oSchema.id] = {};
            oSchema.ops.forEach(function (oOp) {
                $scope.data[oSchema.id][oOp.v] = 0;
            });
        }

        $scope.data[oSchema.id][oOption.v] = number;
    };
    $scope.lessScore = function (schemaId, opIndex, number) {
        var oSchema, oOption;

        if (!$scope.schemasById) return false;
        if (!(oSchema = $scope.schemasById[schemaId])) return false;
        if (!(oOption = oSchema.ops[opIndex])) return false;
        if ($scope.data[oSchema.id] === undefined) {
            return false;
        }
        return $scope.data[oSchema.id][oOption.v] >= number;
    };
    /* 切换轮次，获取和轮次对应的数据，如果有多条数据怎么办？返回最后填写的1条 */
    $scope.shiftRound = function (oRound) {
        fnGetRecordByRound(oRound);
    };
    /* 当前轮次下新建记录 */
    $scope.newRecord = function () {
        $scope.data = {
            member: {}
        };
        fnAfterGetRecord({
            round: $scope.record.round ? $scope.record.round : _oApp.appRound
        });
    };
    $scope.doAction = function (event, oAction) {
        switch (oAction.name) {
            case 'submit':
                $scope.submit(event, oAction.next);
                break;
            case 'gotoPage':
                $scope.gotoPage(event, oAction.next);
                break;
            case 'save':
                $scope.submit(event, oAction.next, 'save');
                break;
            case 'closeWindow':
                $scope.closeWindow();
                break;
        }
    };
    $scope.$on('xxt.app.enroll.ready', function (event, params) {
        var schemasById, pasteContains;

        _oApp = params.app;
        _oPage = params.page;
        _StateCacheKey = 'xxt.app.enroll:' + _oApp.id + '.user:' + $scope.user.uid + '.cacheKey';
        $scope.schemasById = schemasById = _oApp._schemasById;
        /* 不再支持在页面中直接显示按钮 */
        fnHidePageActions();
        /* 用户已经登记过或保存过，恢复之前的数据 */
        fnGetRecord();
        /* 活动轮次 */
        new enlTask(_oApp).list('question').then(function (tasks) {
            $scope.questionTasks = tasks;
            if (tasks.length === 1) {
                $scope.forQuestionTask = tasks[0].id;
            }
        });
        /* 活动轮次 */
        _tkRound = new enlRound(_oApp);
        _tkRound.list().then(function (oResult) {
            $scope.rounds = oResult.rounds;
        });
        /*页面阅读日志*/
        $scope.logAccess();
        /* 微信不支持上传文件，指导用户进行处理 */
        if (/MicroMessenger|iphone|ipad/i.test(navigator.userAgent)) {
            if (_oApp.entryRule && _oApp.entryRule.scope && _oApp.entryRule.scope.member === 'Y') {
                for (var i = 0, ii = params.page.dataSchemas.length; i < ii; i++) {
                    if (params.page.dataSchemas[i].schema.type === 'file') {
                        var domTip, evt;
                        domTip = document.querySelector('#wxUploadFileTip');
                        evt = document.createEvent("HTMLEvents");
                        evt.initEvent("show", false, false);
                        domTip.dispatchEvent(evt);
                        break;
                    }
                }
            }
        }
        /*动态添加粘贴图片*/
        if (!$scope.isSmallLayout) {
            pasteContains = document.querySelectorAll('ul.img-tiles');
            angular.forEach(pasteContains, function (pastecontain) {
                var oSchema, html, $html;
                oSchema = schemasById[pastecontain.getAttribute('name')];
                html = '<li class="img-picker img-edit">';
                html += '<button class="btn btn-default" ng-click="pasteImage(\'' + oSchema.id + '\',$event,' + (oSchema.count || 1) + ')">点击按钮<br>Ctrl+V<br>粘贴截图';
                html += '<div contenteditable="true" tabindex="-1" style="width:1px;height:1px;position:fixed;left:-100px;overflow:hidden;"></div>';
                html += '</button>';
                html += '</li>';
                $html = $compile(html)($scope);
                angular.element(pastecontain).append($html);
            });
        }
    });
}]);