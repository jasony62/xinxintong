define(['schema', 'wrap'], function (schemaLib, wrapLib) {
    'use strict';
    var ngMod = angular.module('schema.enroll', []);
    ngMod.provider('srvEnrollSchema', function () {
        var _siteId;
        this.config = function (siteId) {
            _siteId = siteId;
        };
        this.$get = ['$uibModal', '$q', 'http2', 'srv' + window.MATTER_TYPE + 'App', 'srvEnrollPage', function ($uibModal, $q, http2, srvApp, srvAppPage) {
            var _self = {
                makePagelet: function (content) {
                    var deferred = $q.defer();
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/pagelet.html',
                        controller: ['$scope', '$uibModalInstance', 'mediagallery', function ($scope2, $mi, mediagallery) {
                            var tinymceEditor;
                            $scope2.reset = function () {
                                tinymceEditor.setContent('');
                            };
                            $scope2.ok = function () {
                                var html = tinymceEditor.getContent();
                                tinymceEditor.remove();
                                $mi.close({
                                    html: html
                                });
                            };
                            $scope2.cancel = function () {
                                tinymceEditor.remove();
                                $mi.dismiss();
                            };
                            $scope2.$on('tinymce.multipleimage.open', function (event, callback) {
                                var options = {
                                    callback: callback,
                                    multiple: true,
                                    setshowname: true
                                };
                                mediagallery.open(_siteId, options);
                            });
                            $scope2.$on('tinymce.instance.init', function (event, editor) {
                                var page;
                                tinymceEditor = editor;
                                editor.setContent(content);
                            });
                        }],
                        size: 'lg',
                        backdrop: 'static'
                    }).result.then(function (result) {
                        deferred.resolve(result);
                    });
                    return deferred.promise;
                },
                /**
                 * 更新题目定义
                 */
                update: function (oUpdatedSchema, oBeforeState, prop) {
                    if (prop) {
                        switch (prop) {
                            case 'requireScore':
                                if (oUpdatedSchema.scoreMode === undefined) {
                                    oUpdatedSchema.scoreMode = 'evaluation';
                                }
                                break;
                        }
                    }
                    if (oUpdatedSchema.format === 'number') {
                        if (oUpdatedSchema.scoreMode === 'evaluation') {
                            if (oUpdatedSchema.weight === undefined) {
                                oUpdatedSchema.weight = 1;
                            }
                        }
                    }
                    srvApp.get().then(function (oApp) {
                        oApp.pages.forEach(function (oPage) {
                            oPage.updateSchema(oUpdatedSchema, oBeforeState);
                        });
                    });
                },
                submitChange: function (changedPages) {
                    var deferred = $q.defer();
                    srvApp.get().then(function (oApp) {
                        var updatedAppProps = ['dataSchemas'],
                            oSchema, oNicknameSchema, oAppNicknameSchema;
                        for (var i = oApp.dataSchemas.length - 1; i >= 0; i--) {
                            oSchema = oApp.dataSchemas[i];
                            if (oSchema.required === 'Y') {
                                if (oSchema.type === 'shorttext') {
                                    if (oSchema.title === '姓名') {
                                        oNicknameSchema = oSchema;
                                        break;
                                    }
                                    if (oSchema.title.indexOf('姓名') !== -1) {
                                        if (!oNicknameSchema || oSchema.title.length < oNicknameSchema.title.length) {
                                            oNicknameSchema = oSchema;
                                        }
                                    } else if (oSchema.format && oSchema.format === 'name') {
                                        oNicknameSchema = oSchema;
                                    }
                                }
                            }
                        }
                        if (oNicknameSchema) {
                            if (oAppNicknameSchema = oApp.assignedNickname) {
                                if (oAppNicknameSchema.schema) {
                                    if (oAppNicknameSchema.schema.id !== '') {
                                        oAppNicknameSchema.schema.id = oNicknameSchema.id;
                                        updatedAppProps.push('assignedNickname');
                                    }
                                } else {
                                    oAppNicknameSchema.valid = 'Y';
                                    oAppNicknameSchema.schema = {
                                        id: oNicknameSchema.id
                                    };
                                    updatedAppProps.push('assignedNickname');
                                }
                            }
                        } else {
                            if (oApp.assignedNickname) {
                                if (oApp.assignedNickname.schema)
                                    delete oApp.assignedNickname.schema;
                                if (oApp.assignedNickname.valid === 'Y')
                                    delete oApp.assignedNickname.valid;
                                updatedAppProps.push('assignedNickname');
                            }
                        }
                        srvApp.update(updatedAppProps).then(function (oUpdatedApp) {
                            function fnUpdateOnePage(index) {
                                srvAppPage.update(changedPages[index], ['dataSchemas', 'html']).then(function () {
                                    index++;
                                    if (index === changedPages.length) {
                                        deferred.resolve();
                                    } else {
                                        fnUpdateOnePage(index);
                                    }
                                });
                            }
                            http2.merge(oApp.dataSchemas, oUpdatedApp.dataSchemas);
                            if (!changedPages || changedPages.length === 0) {
                                deferred.resolve();
                            } else {
                                fnUpdateOnePage(0);
                            }
                        });
                    });
                    return deferred.promise;
                }
            };
            return _self;
        }];
    });
    /**
     * 所有题目
     */
    ngMod.controller('ctrlSchemaList', ['$scope', '$timeout', '$sce', '$uibModal', 'noticebox', 'http2', 'CstApp', 'srv' + window.MATTER_TYPE + 'App', 'srvEnrollPage', 'srvEnrollSchema',
        function ($scope, $timeout, $sce, $uibModal, noticebox, http2, CstApp, srvApp, srvAppPage, srvEnrollSchema) {
            $scope.activeSchema = null;
            $scope.CstApp = CstApp;

            $scope.assocApp = function (appId) {
                var oApp, assocApp;
                oApp = $scope.app;
                if (oApp.enrollApp && oApp.enrollApp.id === appId) {
                    return oApp.enrollApp;
                } else if (oApp.groupApp && oApp.groupApp.id === appId) {
                    return oApp.groupApp;
                } else {
                    return false;
                }
            };
            $scope.assocAppSchema = function (oSchema) {
                var oAssocApp, oAssocSchema;
                if (oAssocApp = $scope.assocApp(oSchema.fromApp)) {
                    if (undefined === oAssocApp._schemasById) {
                        oAssocApp._schemasById = {};
                        oAssocApp.dataSchemas.forEach(function (oAssocSchema) {
                            oAssocApp._schemasById[oAssocSchema.id] = oAssocSchema;
                        });
                    }
                    oAssocSchema = oAssocApp._schemasById[oSchema.id];
                }
                return oAssocSchema;
            };
            $scope.updConfig = function (oActiveSchema) {
                srvApp.get().then(function (oApp) {
                    var pages, oPage;
                    pages = oApp.pages;
                    for (var i = pages.length - 1; i >= 0; i--) {
                        oPage = pages[i];
                        if (oPage.type === 'I') {
                            oPage.updateSchema(oActiveSchema);
                            srvAppPage.update(oPage, ['dataSchemas', 'html']);
                        }
                    }
                });
            };
            $scope.newSchema = function (type) {
                var newSchema;

                newSchema = schemaLib.newSchema(type, $scope.app);
                $scope._appendSchema(newSchema);

                return newSchema;
            };
            $scope.newMedia = function (mediaType) {
                var oApp = $scope.app;
                $uibModal.open({
                    templateUrl: 'newMedia.html',
                    controller: ['$uibModalInstance', '$scope', '$timeout', 'noticebox', function ($mi, $scope2, $timeout, noticebox) {
                        $timeout(function () {
                            var oResumable = new Resumable({
                                target: '/rest/pl/fe/matter/enroll/attachment/upload?site=' + oApp.siteid + '&app=' + oApp.id,
                                testChunks: false,
                            });
                            oResumable.assignBrowse(document.getElementById('addAttachment'));
                            oResumable.on('fileAdded', function (file, event) {
                                $scope.$apply(function () {
                                    noticebox.progress('开始上传文件');
                                });
                                oResumable.upload();
                            });
                            oResumable.on('progress', function (file, event) {
                                $scope.$apply(function () {
                                    noticebox.progress('正在上传文件：' + Math.floor(oResumable.progress() * 100) + '%');
                                });
                            });
                            oResumable.on('complete', function () {
                                var f, lastModified, posted;
                                f = oResumable.files.pop().file;
                                lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                                posted = {
                                    name: f.name,
                                    size: f.size,
                                    type: f.type,
                                    lastModified: lastModified,
                                    uniqueIdentifier: f.uniqueIdentifier,
                                };
                                http2.post('/rest/pl/fe/matter/enroll/attachment/add?site=' + oApp.siteid + '&app=' + oApp.id, posted).then(function (rsp) {
                                    $scope2.attachment = rsp.data;
                                    noticebox.close();
                                });
                            });
                        });
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.ok = function () {
                            $mi.close($scope2.attachment);
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function (oAttachment) {
                    var oNewSchema, oProto, mediaUrl, html;
                    oProto = {
                        title: oAttachment.name
                    };
                    mediaUrl = '/rest/site/fe/matter/enroll/attachment/get?site=' + oApp.siteid + '&app=' + oApp.id + '&attachment=' + oAttachment.id;
                    oNewSchema = schemaLib.newSchema('html', $scope.app, oProto);
                    switch (mediaType) {
                        case 'vedio':
                            html = '<div><video controls="controls" preload="none" style="width:100%;">';
                            html += '<source src="' + mediaUrl + '" type="' + oAttachment.type + '" />';
                            html += '</video></div>';
                            break;
                        case 'audio':
                            html = '<div><audio controls="controls" preload="none" style="width:100%;">';
                            html += '<source src="' + mediaUrl + '" type="' + oAttachment.type + '" />';
                            html += '</audio></div>';
                            break;
                    }
                    oNewSchema.content = html;
                    oNewSchema.mediaType = mediaType;
                    $scope._appendSchema(oNewSchema);
                });
            };
            $scope.newMember = function (ms, oMsSchema) {
                var oNewSchema = schemaLib.newSchema(oMsSchema.type, $scope.app);

                oNewSchema.mschema_id = ms.id;
                oNewSchema.id = oMsSchema.id;
                oNewSchema.title = oMsSchema.title;
                oNewSchema.format = oMsSchema.format;
                if (oMsSchema.ops) {
                    oNewSchema.ops = oMsSchema.ops;
                }
                $scope._appendSchema(oNewSchema);
                oMsSchema.assocState = 'yes';

                return oNewSchema;
            };
            $scope.newByOtherApp = function (oProtoSchema, oOtherApp, oAfterSchema) {
                var oNewSchema;

                oNewSchema = schemaLib.newSchema(oProtoSchema.type, $scope.app, oProtoSchema);
                oNewSchema.id = oProtoSchema.id;
                oNewSchema.requireCheck = 'Y';
                oNewSchema.fromApp = oOtherApp.id;
                if (oProtoSchema.ops) {
                    oNewSchema.ops = oProtoSchema.ops;
                }
                oProtoSchema.assocState = 'yes';
                $scope._appendSchema(oNewSchema, oAfterSchema);

                return oNewSchema;
            };
            $scope.unassocWithOtherApp = function (oSchema, bOnlyAssocState) {
                var oAssocApp, oAssocSchema;
                if (oSchema.fromApp) {
                    oAssocApp = $scope.assocApp(oSchema.fromApp);
                    for (var i = oAssocApp.dataSchemas.length - 1; i >= 0; i--) {
                        if (oAssocApp.dataSchemas[i].id === oSchema.id) {
                            oAssocSchema = oAssocApp.dataSchemas[i];
                            oAssocSchema.assocState = 'no';
                            break;
                        }
                    }
                    if (!bOnlyAssocState) {
                        delete oSchema.fromApp;
                        delete oSchema.requireCheck;
                        $scope.updSchema(oSchema);
                    }
                }
                return oAssocSchema;
            };
            $scope.assocWithOtherApp = function (oOtherSchema, oAssocApp) {
                var oAppSchema;
                oAppSchema = $scope.app._schemasById[oOtherSchema.id];
                if (oAppSchema) {
                    if (oAppSchema.type !== oOtherSchema.type) {
                        alert('题目【' + oOtherSchema.title + '】和【' + oAppSchema.title + '】的类型不一致，无法关联');
                        return;
                    }
                    if (oAppSchema.title !== oOtherSchema.title) {
                        alert('题目【' + oOtherSchema.title + '】和【' + oAppSchema.title + '】的名称不一致，无法关联');
                        return;
                    }
                    oAppSchema.fromApp = oAssocApp.id;
                    oAppSchema.requireCheck = 'Y';
                    oOtherSchema.assocState = 'yes';
                    $scope.updSchema(oAppSchema);
                }
            };
            $scope.$watch('app', function (oApp) {
                if (oApp) {
                    $scope.$watch('mschemasById', function (oMschemasById) {
                        var oMschema;
                        if (oMschemasById) {
                            for (var msid in oMschemasById) {
                                oMschema = oMschemasById[msid];
                                if (oMschema._schemas) {
                                    oMschema._schemas.forEach(function (oMsSchema) {
                                        if (oApp._schemasById[oMsSchema.id] === undefined) {
                                            oMsSchema.assocState = '';
                                        } else if (oApp._schemasById[oMsSchema.id].mschema_id === msid) {
                                            oMsSchema.assocState = 'yes';
                                        } else {
                                            oMsSchema.assocState = 'no';
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            });
            $scope.unassocWithMschema = function (oSchema, bOnlyAssocState) {
                var oAssocMschema, oMsSchema, oBefore;
                if (oSchema.mschema_id) {
                    oBefore = angular.copy(oSchema);
                    oAssocMschema = $scope.mschemasById[oSchema.mschema_id];
                    if (oMsSchema = $scope.app._schemasById[oSchema.id]) {
                        oMsSchema.assocState = 'no';
                    }
                    if (!bOnlyAssocState) {
                        delete oSchema.mschema_id;
                        $scope.updSchema(oSchema, oBefore);
                    }
                }
                return oMsSchema;
            };
            $scope.assocWithMschema = function (oMsSchema, oMschema) {
                var oAppSchema, oBefore;
                oAppSchema = $scope.app._schemasById[oMsSchema.id];
                if (oAppSchema) {
                    if (oAppSchema.title !== oMsSchema.title) {
                        alert('题目【' + oMsSchema.title + '】和【' + oAppSchema.title + '】的名称不一致，无法关联');
                        return;
                    }
                    if (oAppSchema.id !== oMsSchema.id) {
                        alert('题目【' + oMsSchema.title + '】和【' + oAppSchema.title + '】的ID不一致，无法关联');
                        return;
                    }
                    oBefore = angular.copy(oAppSchema);
                    oAppSchema.mschema_id = oMschema.id;
                    oMsSchema.assocState = 'yes';
                    $scope.updSchema(oAppSchema, oBefore);
                }
            };
            $scope.copySchema = function (schema) {
                var newSchema = angular.copy(schema);

                newSchema.id = 's' + (new Date * 1);
                newSchema.title += '-2';
                delete newSchema.fromApp;
                delete newSchema.requireCheck;
                $scope._appendSchema(newSchema, schema);

                return newSchema;
            };
            /**
             * 从其他活动中引入题目
             */
            $scope.importByOther = function () {
                var _oApp;
                _oApp = $scope.app;
                http2.post('/rest/script/time', {
                    html: {
                        'other': '/views/default/pl/fe/matter/enroll/component/schema/importByOther'
                    }
                }).then(function (rsp) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/importByOther.html?' + rsp.data.html.other.time,
                        controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                            var oPage, oResult, oFilter;
                            $scope2.app = _oApp;
                            $scope2.page = oPage = {};
                            $scope2.result = oResult = {
                                make: 'copy',
                                target: {
                                    ops: [],
                                    range: {}
                                }
                            };
                            $scope2.filter = oFilter = {};
                            $scope2.selectApp = function () {
                                var dataSchemas;
                                $scope2.dataSchemas = [];
                                oResult.schemas = [];
                                if (oResult.fromApp && angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                    dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                                    if (oResult.make === 'copy') {
                                        $scope2.dataSchemas = dataSchemas;
                                    } else {
                                        switch (oResult.purpose) {
                                            case 'optionByInput':
                                            case 'scoreByInput':
                                                dataSchemas.forEach(function (oSchema) {
                                                    if (/shorttext|longtext/.test(oSchema.type)) {
                                                        $scope2.dataSchemas.push(oSchema);
                                                    }
                                                });
                                                break;
                                            case 'inputByOption':
                                                dataSchemas.forEach(function (oSchema) {
                                                    if (/single|multiple/.test(oSchema.type)) {
                                                        $scope2.dataSchemas.push(oSchema);
                                                    }
                                                });
                                                break;
                                            case 'inputByScore':
                                                dataSchemas.forEach(function (oSchema) {
                                                    if (/score/.test(oSchema.type)) {
                                                        $scope2.dataSchemas.push(oSchema);
                                                    }
                                                });
                                                break;
                                        }
                                    }
                                }
                            };
                            $scope2.selectSchema = function (schema) {
                                if (schema._selected) {
                                    oResult.schemas.push(schema);
                                } else {
                                    oResult.schemas.splice(oResult.schemas.indexOf(schema), 1);
                                }
                            };
                            $scope2.ok = function () {
                                $mi.close(oResult);
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope2.doFilter = function () {
                                oPage.at = 1;
                                $scope2.doSearch();
                            };
                            $scope2.doSearch = function () {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid;
                                if (oResult.make === 'rule' && _oApp.mission) {
                                    /* 同一个项目下的题目才可以设置规则 */
                                    url += '&mission=' + _oApp.mission.id;
                                }
                                http2.post(url, {
                                    byTitle: oFilter.byTitle
                                }, {
                                    page: oPage
                                }).then(function (rsp) {
                                    $scope2.apps = rsp.data.apps;
                                    if ($scope2.apps.length) {
                                        oResult.fromApp = $scope2.apps[0];
                                        $scope2.selectApp();
                                    }
                                });
                            };
                            $scope2.disabled = true;
                            $scope2.$watch('result', function (oNew, oOld) {
                                $scope2.disabled = false;
                                if (!oResult.schemas || oResult.schemas.length === 0) $scope2.disabled = true;
                                if (oNew.make !== oOld.make) {
                                    oPage.at = 1;
                                    $scope2.doSearch();
                                } else if (oNew.purpose !== oOld.purpose) {
                                    $scope2.selectApp();
                                }
                                switch (oResult.purpose) {
                                    case 'optionByInput':
                                        if (!oResult.target || !oResult.target.type) {
                                            $scope2.disabled = true;
                                        }
                                        break;
                                    case 'scoreByInput':
                                        if (!oResult.target) {
                                            $scope2.disabled = true;
                                        } else {
                                            var oTarget = oResult.target;
                                            if (!oTarget.ops || oTarget.ops.length === 0) $scope2.disabled = true;
                                            if (!oTarget.range || !oTarget.range.from || !oTarget.range.to) $scope2.disabled = true;
                                        }
                                        break;
                                    case 'inputByOption':
                                    case 'inputByScore':
                                        if (!oResult.target) {
                                            oResult.target = {};
                                        }
                                        if (!oResult.target.limit) {
                                            oResult.target.limit = {
                                                scope: 'top',
                                                num: 1
                                            };
                                        }
                                        break;
                                }
                            }, true);
                            $scope2.doSearch();
                        }],
                        backdrop: 'static',
                        windowClass: 'auto-height',
                        size: 'lg'
                    }).result.then(function (oResult) {
                        var fnGenNewSchema;
                        switch (oResult.make) {
                            case 'copy': // 复制题目
                                fnGenNewSchema = function (oProtoSchema) {
                                    var oNewSchema;
                                    oNewSchema = schemaLib.newSchema(oProtoSchema.type, _oApp, {
                                        id: oProtoSchema.id
                                    });
                                    oProtoSchema.mschema_id && (oNewSchema.mschema_id = oProtoSchema.mschema_id);
                                    oNewSchema.title = oProtoSchema.title;
                                    if (oProtoSchema.ops) {
                                        oNewSchema.ops = oProtoSchema.ops;
                                    }
                                    if (oProtoSchema.range) {
                                        oNewSchema.range = oProtoSchema.range;
                                    }
                                    if (oProtoSchema.count) {
                                        oNewSchema.count = oProtoSchema.count;
                                    }
                                    return oNewSchema;
                                };
                                oResult.schemas.forEach(function (oProtoSchema) {
                                    var oNewSchema;
                                    if (oNewSchema = fnGenNewSchema(oProtoSchema)) {
                                        $scope._appendSchema(oNewSchema);
                                    }
                                });
                                break; //end: 复制题目
                            case 'rule': // 设置生成题目规则
                                if (oResult.schemas && oResult.schemas.length) {
                                    switch (oResult.purpose) {
                                        case 'optionByInput':
                                            if (oResult.target && oResult.target.type && /single|multiple/.test(oResult.target.type)) {
                                                fnGenNewSchema = function (oProtoSchema) {
                                                    if (!/shorttext|longtext/.test(oProtoSchema.type)) {
                                                        return null;
                                                    }
                                                    var oNewSchema, oTarget;
                                                    oNewSchema = schemaLib.newSchema(oResult.target.type, _oApp, {
                                                        id: oProtoSchema.id
                                                    });
                                                    oNewSchema.title = oProtoSchema.title;
                                                    oNewSchema.dsOps = {
                                                        app: {
                                                            id: oResult.fromApp.id,
                                                            title: oResult.fromApp.title
                                                        },
                                                        schema: {
                                                            id: oProtoSchema.id,
                                                            title: oProtoSchema.title,
                                                            type: oProtoSchema.type
                                                        },
                                                    };
                                                    oNewSchema.dsSchema = {
                                                        app: {
                                                            id: oResult.fromApp.id,
                                                            title: oResult.fromApp.title
                                                        },
                                                        schema: {
                                                            id: oProtoSchema.id,
                                                            title: oProtoSchema.title,
                                                            type: oProtoSchema.type
                                                        }
                                                    };
                                                    if (oTarget = oResult.target) {
                                                        if (oNewSchema.type === 'multiple') {
                                                            if (oTarget.limitChoice && oTarget.limitChoice === 'Y') {
                                                                oNewSchema.limitChoice = 'Y';
                                                                if (oTarget.range) {
                                                                    oNewSchema.range = [];
                                                                    if (oTarget.range.from) {
                                                                        oNewSchema.range.push(parseInt(oTarget.range.from));
                                                                        if (oTarget.range.to) oNewSchema.range.push(parseInt(oTarget.range.to));
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    return oNewSchema;
                                                };
                                            }
                                            break;
                                        case 'scoreByInput':
                                            if (oResult.target) {
                                                var oScoreProto;
                                                oScoreProto = {};
                                                oScoreProto.range = [oResult.target.range.from, oResult.target.range.to];
                                                oScoreProto.ops = [];
                                                oResult.target.ops.forEach(function (op, index) {
                                                    oScoreProto.ops.push({
                                                        v: 'v' + (index + 1),
                                                        l: op.l
                                                    });
                                                });
                                                if (oResult.target.requireScore) oScoreProto.requireScore = 'Y';
                                                fnGenNewSchema = function (oProtoSchema) {
                                                    if (!/shorttext|longtext/.test(oProtoSchema.type)) {
                                                        return null;
                                                    }
                                                    var oNewSchema;
                                                    oNewSchema = schemaLib.newSchema('score', _oApp, oScoreProto, {
                                                        id: oProtoSchema.id
                                                    });
                                                    oNewSchema.title = oProtoSchema.title;
                                                    if (oNewSchema.requireScore === 'Y') oNewSchema.scoreMode = 'evaluation';
                                                    oNewSchema.dsSchema = {
                                                        app: {
                                                            id: oResult.fromApp.id,
                                                            title: oResult.fromApp.title
                                                        },
                                                        schema: {
                                                            id: oProtoSchema.id,
                                                            title: oProtoSchema.title,
                                                            type: oProtoSchema.type
                                                        }
                                                    };
                                                    return oNewSchema;
                                                };
                                            }
                                            break;
                                        case 'inputByOption':
                                            fnGenNewSchema = function (oProtoSchema) {
                                                var oNewSchema;
                                                oNewSchema = schemaLib.newSchema('longtext', _oApp, {
                                                    id: oProtoSchema.id
                                                });
                                                oNewSchema.title = oProtoSchema.title;
                                                oNewSchema.dsSchema = {
                                                    app: {
                                                        id: oResult.fromApp.id,
                                                        title: oResult.fromApp.title
                                                    },
                                                    schema: {
                                                        id: oProtoSchema.id,
                                                        title: oProtoSchema.title,
                                                        type: oProtoSchema.type
                                                    },
                                                    limit: oResult.target.limit
                                                };
                                                return oNewSchema;
                                            };
                                            break;
                                        case 'inputByScore':
                                            fnGenNewSchema = function (oProtoSchema) {
                                                var oNewSchema;
                                                oNewSchema = schemaLib.newSchema('longtext', _oApp, {
                                                    id: oProtoSchema.id
                                                });
                                                oNewSchema.title = oProtoSchema.title;
                                                oNewSchema.dsSchema = {
                                                    app: {
                                                        id: oResult.fromApp.id,
                                                        title: oResult.fromApp.title
                                                    },
                                                    schema: {
                                                        id: oProtoSchema.id,
                                                        title: oProtoSchema.title,
                                                        type: oProtoSchema.type
                                                    },
                                                    limit: oResult.target.limit
                                                };
                                                return oNewSchema;
                                            };
                                            break;
                                    }
                                    if (fnGenNewSchema) {
                                        oResult.schemas.forEach(function (oProtoSchema) {
                                            var oNewSchema;
                                            if (oNewSchema = fnGenNewSchema(oProtoSchema)) {
                                                if (oResult.requireGroup) {
                                                    var oNewGroupSchema;
                                                    oNewGroupSchema = schemaLib.newSchema('html', _oApp, {
                                                        'id': 'g' + oProtoSchema.id,
                                                        title: oProtoSchema.title
                                                    });
                                                    oNewGroupSchema.content = '<div>' + oProtoSchema.title + '</div>';
                                                    oNewGroupSchema.dsSchema = {
                                                        app: {
                                                            id: oResult.fromApp.id,
                                                            title: oResult.fromApp.title
                                                        },
                                                        schema: {
                                                            id: oProtoSchema.id,
                                                            title: oProtoSchema.title,
                                                            type: oProtoSchema.type
                                                        }
                                                    };
                                                    $scope._appendSchema(oNewGroupSchema);
                                                    oNewSchema.parent = {
                                                        id: oNewGroupSchema.id,
                                                        type: oNewGroupSchema.type
                                                    };
                                                }
                                                $scope._appendSchema(oNewSchema);
                                            }
                                        });
                                    }
                                }
                                break; //end: 生成规则
                            case 'schema': // 直接生成题目
                                if (oResult.schemas && oResult.schemas.length) {
                                    var url, oConfig, targetSchemas, oProto, oTarget;
                                    targetSchemas = [];
                                    oResult.schemas.forEach(function (oSchema) {
                                        targetSchemas.push({
                                            id: oSchema.id,
                                            type: oSchema.type
                                        });
                                    });
                                    switch (oResult.purpose) {
                                        case 'inputByOption':
                                        case 'inputByScore':
                                            oConfig = {
                                                schemas: targetSchemas,
                                                limit: oResult.target.limit
                                            };
                                            break;
                                        case 'optionByInput':
                                            if (oTarget = oResult.target) {
                                                oProto = {
                                                    type: oTarget.type
                                                }
                                                if (oTarget.type === 'multiple') {
                                                    if (oTarget.limitChoice && oTarget.limitChoice === 'Y') {
                                                        oProto.limitChoice = 'Y';
                                                        if (oTarget.range) {
                                                            oProto.range = [];
                                                            if (oTarget.range.from) {
                                                                oProto.range.push(parseInt(oTarget.range.from));
                                                                if (oTarget.range.to) oProto.range.push(parseInt(oTarget.range.to));
                                                            }
                                                        }
                                                    }
                                                }
                                                oConfig = {
                                                    schemas: targetSchemas,
                                                    proto: oProto
                                                };
                                            }
                                            break;
                                        case 'scoreByInput':
                                            if (oTarget = oResult.target) {
                                                if (oTarget.range && oTarget.range.from && oTarget.range.to && oTarget.ops && oTarget.ops.length) {
                                                    oProto = {};
                                                    oProto.range = [oTarget.range.from, oTarget.range.to];
                                                    oProto.ops = [];
                                                    oTarget.ops.forEach(function (op, index) {
                                                        oProto.ops.push({
                                                            v: 'v' + (index + 1),
                                                            l: op.l
                                                        });
                                                    });
                                                    if (oTarget.requireScore) oProto.requireScore = true;
                                                }
                                            }
                                            oConfig = {
                                                schemas: targetSchemas,
                                                proto: oProto
                                            };
                                            break;
                                    }
                                    if (oConfig) {
                                        url = '/rest/pl/fe/matter/enroll/schema/' + oResult.purpose;
                                        url += '?app=' + _oApp.id;
                                        url += '&targetApp=' + oResult.fromApp.id;
                                        http2.post(url, oConfig).then(function (rsp) {
                                            if (rsp.data.length) {
                                                rsp.data.forEach(function (oNewSchema) {
                                                    $scope._appendSchema(oNewSchema);
                                                });
                                            }
                                        });
                                    }
                                }
                                break; //end: 直接生成题目
                        }
                    });
                });
            };
            $scope.makePagelet = function (schema, prop) {
                prop = prop || 'content';
                srvEnrollSchema.makePagelet(schema[prop] || '').then(function (result) {
                    if (prop === 'content') {
                        schema.title = $(result.html).text();
                    }
                    schema[prop] = result.html;
                    $scope.updSchema(schema);
                });
            };
            $scope.setOptGroup = function (oSchema) {
                if (!oSchema || !/single|multiple/.test(oSchema.type)) {
                    return false;
                }
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/setOptGroup.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        function genId() {
                            var newKey = 1;
                            _groups.forEach(function (oGroup) {
                                var gKey;
                                gKey = parseInt(oGroup.i.split('_')[1]);
                                if (gKey >= newKey) {
                                    newKey = gKey + 1;
                                }
                            });
                            return 'i_' + newKey;
                        }
                        var _oSchema, _groups, _options, singleSchemas;
                        _oSchema = angular.copy(oSchema);
                        if (_oSchema.optGroups === undefined) {
                            _oSchema.optGroups = [];
                        }
                        if (_oSchema.ops === undefined) {
                            _oSchema.ops = [];
                        }
                        singleSchemas = []; //所有单项选择题
                        $scope.app.dataSchemas.forEach(function (oAppSchema) {
                            if (oAppSchema.type === 'single' && oAppSchema.id !== oSchema.id) {
                                singleSchemas.push(oAppSchema);
                            }
                        });
                        $scope2.singleSchemas = singleSchemas;
                        $scope2.groups = _groups = _oSchema.optGroups;
                        $scope2.options = _options = _oSchema.ops;
                        $scope2.addGroup = function () {
                            var oNewGroup;
                            oNewGroup = {
                                i: genId(),
                                l: '分组-' + (_groups.length + 1)
                            };
                            _groups.push(oNewGroup);
                            $scope2.toggleGroup(oNewGroup);
                        };
                        $scope2.toggleGroup = function (oGroup) {
                            var oAppSchema;
                            $scope2.activeGroup = oGroup;
                            $scope2.activeOps = [];
                            if (oGroup.assocOp && oGroup.assocOp.schemaId) {
                                for (var i = 0, ii = singleSchemas.length; i < ii; i++) {
                                    oAppSchema = singleSchemas[i];
                                    if (oAppSchema.id === oGroup.assocOp.schemaId) {
                                        oGroup.assocOp.schema = oAppSchema;
                                        break;
                                    }
                                }
                            }
                        };
                        $scope2.ok = function () {
                            _groups.forEach(function (oGroup) {
                                if (oGroup.assocOp && oGroup.assocOp.schema) {
                                    oGroup.assocOp.schemaId = oGroup.assocOp.schema.id;
                                    delete oGroup.assocOp.schema;
                                }
                            });
                            $mi.close({
                                groups: _groups,
                                options: _options
                            });
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function (groupAndOps) {
                    oSchema.ops = groupAndOps.options;
                    oSchema.optGroups = groupAndOps.groups;
                    $scope.updSchema(oSchema);
                });
            };
            $scope.setSchemaParent = function (oSchema) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setSchemaParent.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var htmlSchemas, oResult;
                        htmlSchemas = []; //所有单项选择题
                        $scope2.result = oResult = {};
                        $scope.app.dataSchemas.forEach(function (oAppSchema) {
                            if (oAppSchema.type === 'html') {
                                htmlSchemas.push(angular.copy(oAppSchema));
                                if (oSchema.parent && oAppSchema.id === oSchema.parent.id) {
                                    oResult.id = oAppSchema.id;
                                    oResult.type = oAppSchema.type;
                                    oResult.title = oAppSchema.title;
                                }
                            }
                        });
                        $scope2.htmlSchemas = htmlSchemas;
                        $scope2.selectSchema = function (oSchema) {
                            oResult.id = oSchema.id;
                            oResult.title = oSchema.title;
                            oResult.type = oSchema.type;
                        };
                        $scope2.ok = function () {
                            $mi.close(oResult);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function (oResult) {
                    if (oResult.id && oResult.type && oResult.title) {
                        oSchema.parent = oResult;
                        $scope.updSchema(oSchema);
                    }
                });
            };
            $scope.setVisibility = function (oSchema) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setVisibility.html?_=2',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var _optSchemas, _rules, _oBeforeRules;
                        _optSchemas = []; //所有选择题
                        _rules = []; // 当前题目的可见规则
                        if (oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                            _oBeforeRules = {};
                            oSchema.visibility.rules.forEach(function (oRule) {
                                if (_oBeforeRules[oRule.schema]) {
                                    _oBeforeRules[oRule.schema].push(oRule.op);
                                } else {
                                    _oBeforeRules[oRule.schema] = [oRule.op];
                                }
                            });
                        }
                        $scope.app.dataSchemas.forEach(function (oAppSchema) {
                            if (/single|multiple/.test(oAppSchema.type) && oAppSchema.id !== oSchema.id) {
                                _optSchemas.push(oAppSchema);
                                if (_oBeforeRules && _oBeforeRules[oAppSchema.id]) {
                                    var oBeforeRule;
                                    for (var i = 0, ii = oAppSchema.ops.length; i < ii; i++) {
                                        if (_oBeforeRules[oAppSchema.id].indexOf(oAppSchema.ops[i].v) !== -1) {
                                            oBeforeRule = {
                                                schema: oAppSchema
                                            };
                                            oBeforeRule.op = oAppSchema.ops[i];
                                            _rules.push(oBeforeRule);
                                        }
                                    }
                                }
                            }
                        });
                        $scope2.data = {
                            logicOR: false
                        };
                        if (oSchema.visibility && oSchema.visibility.logicOR) {
                            $scope2.data.logicOR = true;
                        }
                        $scope2.optSchemas = _optSchemas;
                        $scope2.rules = _rules;
                        $scope2.addRule = function () {
                            _rules.push({});
                        };
                        $scope2.removeRule = function (oRule) {
                            _rules.splice(_rules.indexOf(oRule), 1);
                        };
                        $scope2.cleanRule = function () {
                            _rules.splice(0, _rules.length);
                        };
                        $scope2.ok = function () {
                            var oConfig = {
                                rules: [],
                                logicOR: $scope2.data.logicOR
                            };
                            _rules.forEach(function (oRule) {
                                oConfig.rules.push({
                                    schema: oRule.schema.id,
                                    op: oRule.op.v
                                });
                            });
                            oSchema.visibility = oConfig;
                            $scope.updSchema(oSchema);
                            $mi.close(oSchema);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                });
            };
            $scope.setDefaultValue = function (oSchema) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/setDefaultValue.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var _oData;
                        $scope2.data = _oData = {};
                        $scope2.schema = angular.copy(oSchema);
                        if ($scope2.schema) {
                            _oData.defaultValue = $scope2.schema.defaultValue;
                        }
                        $scope2.ok = function () {
                            var bChanged = false;
                            switch ($scope2.schema.type) {
                                case 'single':
                                    if (oSchema.defaultValue !== _oData.defaultValue) {
                                        bChanged = true;
                                        if (_oData.defaultValue) {
                                            oSchema.defaultValue = _oData.defaultValue;
                                        } else {
                                            delete oSchema.defaultValue;
                                        }
                                    }
                                    break;
                                case 'multiple':
                                    if (!angular.equals(_oData.defaultValue, oSchema.defaultValue)) {
                                        bChanged = true;
                                        var validKeys;
                                        validKeys = [];
                                        if (_oData.defaultValue) {
                                            for (var key in _oData.defaultValue) {
                                                if (_oData.defaultValue[key]) {
                                                    validKeys.push(key);
                                                }
                                            }
                                        }
                                        if (validKeys.length) {
                                            oSchema.defaultValue = {};
                                            validKeys.forEach(function (key) {
                                                oSchema.defaultValue[key] = true;
                                            });
                                        } else {
                                            delete oSchema.defaultValue;
                                        }
                                    }
                                    break;
                            }
                            if (bChanged) {
                                $scope.updSchema(oSchema);
                            }
                            $mi.close(oSchema);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                });
            };
            $scope.setHistoryAssoc = function (oSchema) {
                var _oApp;
                _oApp = $scope.app;
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/setHistoryAssoc.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var options = [];
                        $scope2.schemas = [];
                        _oApp.dataSchemas.forEach(function (oOther) {
                            if (oOther.id !== oSchema.id && 'shorttext' === oOther.type && oOther.history === 'Y') {
                                $scope2.schemas.push(oOther);
                                options.push(oOther.id);
                            }
                        });
                        $scope2.result = {};
                        if (oSchema.historyAssoc && oSchema.historyAssoc.length) {
                            oSchema.historyAssoc.forEach(function (schemaId) {
                                if (options.indexOf(schemaId) !== -1) {
                                    $scope2.result[schemaId] = true;
                                }
                            });
                        }
                        $scope2.ok = function () {
                            $mi.close($scope2.result);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                    }],
                    backdrop: 'static',
                }).result.then(function (oResult) {
                    oSchema.historyAssoc = [];
                    for (var schemaId in oResult) {
                        if (oResult[schemaId]) oSchema.historyAssoc.push(schemaId);
                    }
                    $scope.updSchema(oSchema);
                });
            };
            $scope.setDataSource = function (oSchema) {
                var _oApp;
                _oApp = $scope.app;
                http2.post('/rest/script/time', {
                    html: {
                        'source': '/views/default/pl/fe/matter/enroll/component/schema/setDataSource'
                    }
                }).then(function (rsp) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setDataSource.html?' + rsp.data.html.source.time,
                        controller: ['$scope', '$uibModalInstance', 'tkEnrollApp', function ($scope2, $mi, tkEnlApp) {
                            var FnValidSchemas, _oPage, _oFilter, _oResult;
                            FnValidSchemas = {
                                score: function (oSchema2) {
                                    return oSchema2.requireScore === 'Y';
                                },
                                score_rank: function (oSchema2) {
                                    return oSchema2.requireScore === 'Y';
                                },
                                input: function (oSchema2) {
                                    return 'shorttext' === oSchema2.type && oSchema2.format === 'number';
                                },
                                option: function (oSchema2) {
                                    return /single|multiple/.test(oSchema2.type) && oSchema2.dsOps;
                                }
                            };
                            $scope2.schema = oSchema;
                            $scope2.page = _oPage = {};
                            $scope2.filter = _oFilter = {};
                            $scope2.schemas = [];
                            $scope2.result = _oResult = {
                                selected: [],
                                reset: function () {
                                    this.type = null;
                                    this.selected = [];
                                }
                            };
                            if (oSchema.ds) {
                                if (oSchema.ds.app) {
                                    tkEnlApp.get(oSchema.ds.app.id).then(function (oSourceApp) {
                                        _oResult.fromApp = oSourceApp;
                                        _oResult.type = oSchema.ds.type;
                                        if (_oResult.type === 'act') {
                                            _oResult.selected = oSchema.ds.name;
                                        } else if (FnValidSchemas[_oResult.type]) {
                                            $scope2.schemas = _oResult.fromApp.dataSchemas.filter(FnValidSchemas[_oResult.type]);
                                            $scope2.schemas.forEach(function (oSchema2) {
                                                if (oSchema.ds.schema.indexOf(oSchema2.id) !== -1) {
                                                    _oResult.selected.push(oSchema2.id);
                                                }
                                            });
                                        }
                                    });
                                }
                            }
                            $scope2.selectApp = function () {
                                if (angular.isString(_oResult.fromApp.data_schemas) && _oResult.fromApp.data_schemas) {
                                    _oResult.fromApp.dataSchemas = JSON.parse(_oResult.fromApp.data_schemas);
                                }
                                _oResult.reset();
                            };
                            $scope2.$watch('result.type', function (newType) {
                                if (newType && _oResult.fromApp) {
                                    if (FnValidSchemas[newType]) {
                                        $scope2.schemas = _oResult.fromApp.dataSchemas.filter(FnValidSchemas[newType]);
                                    }
                                }
                            });
                            $scope2.$watch('result', function (oNewResult) {
                                $scope2.disabled = false;
                                if (!_oResult.fromApp) {
                                    $scope2.disabled = true;
                                } else if (!_oResult.type) {
                                    $scope2.disabled = true;
                                } else if (!_oResult.selected || _oResult.selected.length === 0) {
                                    $scope2.disabled = true;
                                }
                            }, true);
                            $scope2.ok = function () {
                                var fromApp, oConfig;
                                if (fromApp = _oResult.fromApp) {
                                    oConfig = {
                                        app: {
                                            id: fromApp.id,
                                            title: fromApp.title
                                        },
                                        type: _oResult.type,
                                        schema: _oResult.selected
                                    };
                                    $mi.close(oConfig);
                                }
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope2.doSearch = function (pageAt) {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid;
                                if (_oApp.mission) {
                                    url += '&mission=' + _oApp.mission.id;
                                }
                                pageAt && (_oPage.at = pageAt);
                                http2.post(url, {
                                    byTitle: _oFilter.byTitle
                                }, {
                                    page: _oPage
                                }).then(function (rsp) {
                                    $scope2.apps = rsp.data.apps;
                                    if ($scope2.apps.length) {
                                        if (!_oResult.fromApp) {
                                            _oResult.fromApp = $scope2.apps[0];
                                            $scope2.selectApp();
                                        }
                                    }
                                });
                            };
                            $scope2.disabled = true;
                            $scope2.doSearch();
                        }],
                        backdrop: 'static',
                        windowClass: 'auto-height',
                        size: 'lg'
                    }).result.then(function (oResult) {
                        if (oResult.app && oResult.type) {
                            oSchema.ds = {
                                app: {
                                    id: oResult.app.id,
                                    title: oResult.app.title
                                },
                                type: oResult.type,
                                schema: oResult.schema
                            }
                            $scope.updSchema(oSchema);
                        }
                    });
                });
            };
            $scope.removeDataSource = function (oSchema) {
                if (oSchema.ds) {
                    delete oSchema.ds;
                    $scope.updSchema(oSchema);
                }
            };
            $scope.setOptionsSource = function (oSchema) {
                var _oApp;
                _oApp = $scope.app;
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setOptionsSource.html?_=1',
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        var oPage, oResult, oAppFilter;
                        $scope2.page = oPage = {};
                        $scope2.schema = oSchema;
                        $scope2.result = oResult = {};
                        $scope2.appFilter = oAppFilter = {};
                        $scope2.dsSchemas = [];
                        $scope2.filterSchemas = [];
                        $scope2.selectApp = function () {
                            $scope2.dsSchemas = [];
                            $scope2.filterSchemas = [];
                            oResult.selected = null;
                            oResult.filters = [];
                            if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                                oResult.fromApp.dataSchemas.forEach(function (oSchema) {
                                    if (/longtext|url/.test(oSchema.type)) {
                                        $scope2.dsSchemas.push(oSchema);
                                    } else if (/shorttext/.test(oSchema.type) && !oSchema.format) {
                                        $scope2.dsSchemas.push(oSchema);
                                    } else if (/single/.test(oSchema.type)) {
                                        $scope2.filterSchemas.push(angular.copy(oSchema));
                                    }
                                });
                            }
                            oResult.selected = null;
                        };
                        $scope2.addFilter = function () {
                            oResult.filters.push({});
                        };
                        $scope2.removeFilter = function (oFilter) {
                            oResult.filters.splice(oResult.filters.indexOf(oFilter), 1);
                        };
                        $scope2.ok = function () {
                            var fromApp;
                            if ((fromApp = oResult.fromApp) && oResult.selected !== undefined) {
                                $mi.close({
                                    action: 'ok',
                                    app: {
                                        id: fromApp.id,
                                        title: fromApp.title
                                    },
                                    schema: $scope2.dsSchemas[parseInt(oResult.selected)],
                                    filters: oResult.filters
                                });
                            } else {
                                $mi.dismiss();
                            }
                        };
                        $scope2.clean = function () {
                            $mi.close({
                                action: 'clean'
                            });
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doSearch = function (pageAt) {
                            var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid;
                            if (_oApp.mission) {
                                url += '&mission=' + _oApp.mission.id;
                            }
                            pageAt && (oPage.at = pageAt);
                            http2.post(url, {
                                byTitle: oAppFilter.byTitle
                            }, {
                                page: oPage
                            }).then(function (rsp) {
                                $scope2.apps = rsp.data.apps;
                                if ($scope2.apps.length) {
                                    oResult.fromApp = $scope2.apps[0];
                                    $scope2.selectApp();
                                }
                            });
                        };
                        $scope2.disabled = true;
                        $scope2.$watch('result', function () {
                            $scope2.disabled = false;
                            if (!oResult.selected) $scope2.disabled = true;
                        }, true);
                        $scope2.doSearch();
                    }],
                    backdrop: 'static',
                    windowClass: 'auto-height',
                    size: 'lg'
                }).result.then(function (oResult) {
                    switch (oResult.action) {
                        case 'ok':
                            if (oResult.app && oResult.schema) {
                                oSchema.dsOps = {
                                    app: {
                                        id: oResult.app.id,
                                        title: oResult.app.title
                                    },
                                    schema: {
                                        id: oResult.schema.id,
                                        title: oResult.schema.title
                                    },
                                }
                                if (oResult.filters && oResult.filters.length) {
                                    oSchema.dsOps.filters = [];
                                    oResult.filters.forEach(function (oFilter) {
                                        var oNewFilter;
                                        if (oFilter.schema && oFilter.op) {
                                            oNewFilter = {
                                                schema: {
                                                    id: oFilter.schema.id,
                                                    type: oFilter.schema.type,
                                                    op: {
                                                        v: oFilter.op.v,
                                                        l: oFilter.op.l
                                                    }
                                                }
                                            };
                                            oSchema.dsOps.filters.push(oNewFilter);
                                        }
                                    });
                                }
                                $scope.updSchema(oSchema);
                            }
                            break;
                        case 'clean':
                            delete oSchema.dsOps;
                            $scope.updSchema(oSchema);
                            break;
                    }
                });
            };
            /**
             * 动态题目设置
             */
            $scope.setSchemaSource = function (oSchema) {
                var _oApp;
                _oApp = $scope.app;
                http2.post('/rest/script/time', {
                    html: {
                        'source': '/views/default/pl/fe/matter/enroll/component/schema/setSchemaSource'
                    }
                }).then(function (rsp) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setSchemaSource.html?_=' + rsp.data.html.source.time,
                        controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                            var oPage, oResult, oAppFilter;
                            $scope2.page = oPage = {};
                            $scope2.schema = oSchema;
                            $scope2.result = oResult = {
                                mode: 'fromData'
                            };
                            $scope2.appFilter = oAppFilter = {};
                            $scope2.dsSchemas = [];
                            $scope2.filterSchemas = [];
                            $scope2.selectApp = function () {
                                $scope2.dsSchemas = [];
                                $scope2.filterSchemas = [];
                                oResult.selected = null;
                                oResult.filters = [];
                                if (angular.isString(oResult.fromApp.data_schemas) && oResult.fromApp.data_schemas) {
                                    oResult.fromApp.dataSchemas = JSON.parse(oResult.fromApp.data_schemas);
                                    if (oResult.fromApp.dataSchemas.length) {
                                        var fnValidSchema;
                                        switch (oResult.mode) {
                                            case 'fromData':
                                                fnValidSchema = function (oSchema) {
                                                    if (/longtext|url|multitext/.test(oSchema.type)) {
                                                        $scope2.dsSchemas.push(oSchema);
                                                    } else if (/shorttext/.test(oSchema.type) && !oSchema.format) {
                                                        $scope2.dsSchemas.push(oSchema);
                                                    } else if (/single/.test(oSchema.type)) {
                                                        $scope2.filterSchemas.push(angular.copy(oSchema));
                                                    }
                                                };
                                                break;
                                            case 'fromScore':
                                                fnValidSchema = function (oSchema) {
                                                    if (/score/.test(oSchema.type) && oSchema.dsSchema) {
                                                        $scope2.dsSchemas.push(angular.copy(oSchema));
                                                    }
                                                };
                                                break;
                                            case 'fromOption':
                                                fnValidSchema = function (oSchema) {
                                                    if (/single|multiple/.test(oSchema.type)) {
                                                        $scope2.dsSchemas.push(angular.copy(oSchema));
                                                    }
                                                };
                                                break;
                                        }
                                        if (fnValidSchema) {
                                            oResult.fromApp.dataSchemas.forEach(fnValidSchema);
                                        }
                                    }
                                }
                                oResult.selected = null;
                            };
                            $scope2.addFilter = function () {
                                oResult.filters.push({});
                            };
                            $scope2.removeFilter = function (oFilter) {
                                oResult.filters.splice(oResult.filters.indexOf(oFilter), 1);
                            };
                            $scope2.ok = function () {
                                var fromApp, oConfig;
                                if ((fromApp = oResult.fromApp) && oResult.selected !== undefined) {
                                    oConfig = {
                                        action: 'ok',
                                        mode: oResult.mode,
                                        app: {
                                            id: fromApp.id,
                                            title: fromApp.title
                                        },
                                        schema: $scope2.dsSchemas[parseInt(oResult.selected)]
                                    };
                                    if (oResult.mode === 'fromData') {
                                        oConfig.filters = oResult.filters;
                                    } else if (/fromScore|fromOption/.test(oResult.mode)) {
                                        oConfig.limit = oResult.limit;
                                    }
                                    $mi.close(oConfig);
                                } else {
                                    $mi.dismiss();
                                }
                            };
                            $scope2.clean = function () {
                                $mi.close({
                                    action: 'clean'
                                });
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope2.doSearch = function (pageAt) {
                                var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid;
                                if (_oApp.mission) {
                                    url += '&mission=' + _oApp.mission.id;
                                }
                                pageAt && (oPage.at = pageAt);
                                http2.post(url, {
                                    byTitle: oAppFilter.byTitle
                                }, {
                                    page: oPage
                                }).then(function (rsp) {
                                    $scope2.apps = rsp.data.apps;
                                    if ($scope2.apps.length) {
                                        oResult.fromApp = $scope2.apps[0];
                                        $scope2.selectApp();
                                    }
                                });
                            };
                            $scope2.disabled = true;
                            $scope2.$watch('result', function (oNew, oOld) {
                                $scope2.disabled = false;
                                if (!oResult.selected) $scope2.disabled = true;
                                if (oNew && oOld) {
                                    if (oNew.mode !== oOld.mode) {
                                        $scope2.selectApp();
                                        if (oNew.mode === 'fromOption') {
                                            if (oResult.limit === undefined) {
                                                oResult.limit = {
                                                    scope: 'top',
                                                    num: 1
                                                };
                                            }
                                        } else if (oNew.mode === 'fromScore') {
                                            if (oResult.limit === undefined) {
                                                oResult.limit = {
                                                    scope: 'top',
                                                    num: 1
                                                };
                                            }
                                        }
                                    }
                                }
                            }, true);
                            $scope2.doSearch();
                        }],
                        backdrop: 'static',
                        windowClass: 'auto-height',
                        size: 'lg'
                    }).result.then(function (oResult) {
                        switch (oResult.action) {
                            case 'ok':
                                if (oResult.app && oResult.schema) {
                                    oSchema.dsSchema = {
                                        app: {
                                            id: oResult.app.id,
                                            title: oResult.app.title
                                        },
                                        schema: {
                                            id: oResult.schema.id,
                                            title: oResult.schema.title,
                                            type: oResult.schema.type
                                        }
                                    }
                                    if (oResult.limit) {
                                        oSchema.dsSchema.limit = oResult.limit;
                                    }
                                    if (oResult.filters && oResult.filters.length) {
                                        oSchema.dsSchema.filters = [];
                                        oResult.filters.forEach(function (oFilter) {
                                            var oNewFilter;
                                            if (oFilter.schema && oFilter.op) {
                                                oNewFilter = {
                                                    schema: {
                                                        id: oFilter.schema.id,
                                                        type: oFilter.schema.type,
                                                        op: {
                                                            v: oFilter.op.v,
                                                            l: oFilter.op.l
                                                        }
                                                    }
                                                };
                                                oSchema.dsSchema.filters.push(oNewFilter);
                                            }
                                        });
                                    }
                                    $scope.updSchema(oSchema);
                                }
                                break;
                            case 'clean':
                                delete oSchema.dsSchema;
                                $scope.updSchema(oSchema);
                                break;
                        }
                    });
                });
            };
            /**
             * oAfterSchema: false - first, undefined - after active schema
             */
            $scope._appendSchema = function (newSchema, oAfterSchema) {
                var oApp, afterIndex, changedPages = [];
                oApp = $scope.app;
                if (oApp._schemasById[newSchema.id]) {
                    alert(CstApp.alertMsg['schema.duplicated']);
                    return;
                }
                if (undefined === oAfterSchema) {
                    oAfterSchema = $scope.activeSchema;
                }
                if (oAfterSchema) {
                    afterIndex = oApp.dataSchemas.indexOf(oAfterSchema);
                    oApp.dataSchemas.splice(afterIndex + 1, 0, newSchema);
                } else if (oAfterSchema === false) {
                    oApp.dataSchemas.splice(0, 0, newSchema);
                } else {
                    oApp.dataSchemas.push(newSchema);
                }
                oApp._schemasById[newSchema.id] = newSchema;
                oApp.pages.forEach(function (oPage) {
                    if (oPage.appendSchema(newSchema, oAfterSchema)) {
                        changedPages.push(oPage);
                    }
                });
                return srvEnrollSchema.submitChange(changedPages);
            };
            $scope._changeSchemaOrder = function (moved) {
                var oApp, i, prevSchema, changedPages;

                oApp = $scope.app;
                i = oApp.dataSchemas.indexOf(moved);
                if (i > 0) prevSchema = oApp.dataSchemas[i - 1];
                changedPages = []
                oApp.pages.forEach(function (oPage) {
                    oPage.moveSchema(moved, prevSchema);
                    changedPages.push(oPage);
                });
                srvEnrollSchema.submitChange(changedPages);
            };
            $scope._removeSchema = function (oRemovedSchema) {
                var oApp = $scope.app,
                    changedPages = [];

                /* 更新定义 */
                oApp.dataSchemas.splice(oApp.dataSchemas.indexOf(oRemovedSchema), 1);
                delete oApp._schemasById[oRemovedSchema.id];
                $scope.app.pages.forEach(function (oPage) {
                    if (oPage.removeSchema(oRemovedSchema)) {
                        changedPages.push(oPage);
                    }
                });
                srvEnrollSchema.submitChange(changedPages).then(function () {
                    var aNewRecycleSchemas, oAssocSchema;
                    /* 放入回收站 */
                    aNewRecycleSchemas = [];
                    for (var i = oApp.recycleSchemas.length - 1; i >= 0; i--) {
                        if (oApp.recycleSchemas[i].id !== oRemovedSchema.id) {
                            aNewRecycleSchemas.push(oApp.recycleSchemas[i]);
                        }
                    }
                    aNewRecycleSchemas.push(oRemovedSchema);
                    oApp.recycleSchemas = aNewRecycleSchemas;
                    srvApp.update('recycleSchemas');
                    /* 去除关联状态 */
                    if (oRemovedSchema.mschema_id) {
                        if (oAssocSchema = $scope.unassocWithMschema(oRemovedSchema, true)) {
                            oAssocSchema.assocState = '';
                        }
                    }
                    if (oRemovedSchema.fromApp) {
                        if (oAssocSchema = $scope.unassocWithOtherApp(oRemovedSchema, true)) {
                            oAssocSchema.assocState = '';
                        }
                    }
                });
            };
            var timerOfUpdate = null;
            $scope.updSchema = function (oSchema, oBeforeState, prop) {
                srvEnrollSchema.update(oSchema, oBeforeState, prop);
                if (timerOfUpdate !== null) {
                    $timeout.cancel(timerOfUpdate);
                }
                timerOfUpdate = $timeout(function () {
                    srvEnrollSchema.submitChange($scope.app.pages).then(function () {
                        if (prop && prop === 'weight') {
                            srvApp.opData().then(function (oData) {
                                if (oData.total > 0 || (oData.length && oData[0].total > 0)) {
                                    $uibModal.open({
                                        templateUrl: '/views/default/pl/fe/matter/enroll/component/renewScore.html',
                                        controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                                            $scope2.ok = function () {
                                                srvApp.renewScoreByRound().then(function () {
                                                    $mi.close();
                                                });
                                            };
                                            $scope2.cancel = function () {
                                                $mi.dismiss();
                                            };
                                        }],
                                        backdrop: 'static'
                                    });
                                }
                            });
                        }
                    });
                }, 1000);
                timerOfUpdate.then(function () {
                    timerOfUpdate = null;
                });
            };
            $scope.removeSchema = function (oRemovedSchema) {
                if (window.confirm('确定从所有页面上删除登记项［' + oRemovedSchema.title + '］？')) {
                    $scope._removeSchema(oRemovedSchema);
                    $scope.activeSchema = null;
                    $scope.activeOption = null;
                }
            };
            $scope.chooseSchema = function (event, oSchema) {
                $scope.activeOption && ($scope.activeOption = null);
                $scope.activeSchema = oSchema;
                if ($scope.app.scenario && oSchema.type === 'multiple') {
                    angular.isString(oSchema.answer) && (oSchema.answer = oSchema.answer.split(','));
                    !$scope.data && ($scope.data = {});
                    angular.forEach(oSchema.answer, function (answer) {
                        $scope.data[answer] = true;
                    })
                }
            };
            $scope.chooseOption = function (event, oSchema, oOption) {
                if (oSchema !== $scope.activeSchema) {
                    $scope.chooseSchema(event, oSchema);
                }
                $scope.activeOption = oOption;
            };
            $scope.schemaHtml = function (schema) {
                if (schema) {
                    var bust = (new Date()).getMinutes();
                    return '/views/default/pl/fe/matter/enroll/schema/' + schema.type + '.html?_=' + bust;
                }
            };
            var _IncludeSchemaTemplates = {
                html: {
                    option: {
                        url: '/views/default/pl/fe/matter/enroll/schema/option'
                    },
                    main: {
                        url: '/views/default/pl/fe/matter/enroll/schema/main'
                    }
                }
            };
            http2.post('/rest/script/time', _IncludeSchemaTemplates).then(function (rsp) {
                angular.merge(_IncludeSchemaTemplates, rsp.data);
            });
            $scope.schemaEditorHtml = function () {
                if ($scope.activeOption) {
                    return '/views/default/pl/fe/matter/enroll/schema/option.html?_=' + (_IncludeSchemaTemplates.html.option.time || 1);
                } else if ($scope.activeSchema) {
                    return '/views/default/pl/fe/matter/enroll/schema/main.html?_=' + (_IncludeSchemaTemplates.html.main.time || 1);
                } else {
                    return '';
                }
            };
            $scope.upSchema = function (oSchema, bGotoTop) {
                var schemas = $scope.app.dataSchemas,
                    index = schemas.indexOf(oSchema);

                if (index > 0) {
                    schemas.splice(index, 1);
                    if (bGotoTop) {
                        schemas.splice(0, 0, oSchema);
                    } else {
                        schemas.splice(index - 1, 0, oSchema);
                    }
                    $scope._changeSchemaOrder(oSchema);
                }
            };
            $scope.downSchema = function (schema, bGotoBottom) {
                var schemas = $scope.app.dataSchemas,
                    index = schemas.indexOf(schema);
                if (index < schemas.length - 1) {
                    schemas.splice(index, 1);
                    if (bGotoBottom) {
                        schemas.push(schema);
                    } else {
                        schemas.splice(index + 1, 0, schema);
                    }
                    $scope._changeSchemaOrder(schema);
                }
            };
            $scope.$on('schemas.orderChanged', function (e, moved) {
                $scope._changeSchemaOrder(moved);
            });
            $scope.showSchemaProto = function ($event) {
                var target = event.target;
                if (target.dataset.isOpen === 'Y') {
                    delete target.dataset.isOpen;
                    $(target).trigger('hide');
                } else {
                    target.dataset.isOpen = 'Y';
                    $(target).trigger('show');
                }
            };
            $scope.addOption = function (schema, afterIndex) {
                var maxSeq = 0,
                    newOp = {
                        l: '新选项'
                    };

                if (schema.ops === undefined) {
                    schema.ops = [];
                }
                schema.ops.forEach(function (op) {
                    var opSeq = parseInt(op.v.substr(1));
                    opSeq > maxSeq && (maxSeq = opSeq);
                });
                newOp.v = 'v' + (++maxSeq);
                if (afterIndex === undefined) {
                    schema.ops.push(newOp);
                } else {
                    schema.ops.splice(afterIndex + 1, 0, newOp);
                }
                $timeout(function () {
                    $scope.$broadcast('xxt.editable.add', newOp);
                });
            };
            $scope.editOption = function (schema, op, prop) {
                prop = prop || 'content';
                srvEnrollSchema.makePagelet(op[prop] || '').then(function (result) {
                    if (prop === 'content') {
                        schema.title = $(result.html).text();
                    }
                    op[prop] = result.html;
                    $scope.updSchema(schema);
                });
            };
            $scope.moveUpOption = function (schema, op) {
                var ops = schema.ops,
                    index = ops.indexOf(op);

                if (index > 0) {
                    ops.splice(index, 1);
                    ops.splice(index - 1, 0, op);
                    $scope.updSchema(schema);
                }
            };
            $scope.moveDownOption = function (schema, op) {
                var ops = schema.ops,
                    index = ops.indexOf(op);

                if (index < ops.length - 1) {
                    ops.splice(index, 1);
                    ops.splice(index + 1, 0, op);
                    $scope.updSchema(schema);
                }
            };
            $scope.removeOption = function (oSchema, oOp) {
                oSchema.ops.splice(oSchema.ops.indexOf(oOp), 1);
                if (oSchema.answer) {
                    if (oSchema.type == 'single') {
                        if (oOp.v == oSchema.answer) {
                            delete oSchema.answer;
                        }
                    } else if (oSchema.type == 'multiple') {
                        oSchema.answer.forEach(function (item, index) {
                            if (oOp.v == item) {
                                oSchema.answer.splice(index, 1);
                            }
                        });
                    }
                }
                $scope.updSchema(oSchema);
            };
            $scope.refreshSchema = function (oSchema) {
                var oApp;
                oApp = $scope.app;
                if (oSchema.id === '_round_id' && oApp.groupApp) {
                    http2.get('/rest/pl/fe/matter/group/team/list?app=' + oApp.groupApp.id).then(function (rsp) {
                        var newOp, opById;
                        if (rsp.data.length) {
                            opById = {};
                            if (oSchema.ops === undefined) {
                                oSchema.ops = [];
                            } else {
                                oSchema.ops.forEach(function (op) {
                                    opById[op.v] = op;
                                });
                            }
                            rsp.data.forEach(function (oTeam) {
                                if (undefined === opById[oTeam.team_id]) {
                                    newOp = {};
                                    newOp.l = oTeam.title;
                                    newOp.v = oTeam.team_id;
                                    oSchema.ops.push(newOp);
                                } else {
                                    newOp = opById[oTeam.team_id];
                                    newOp.l = oTeam.title;
                                }
                            });
                            if (newOp) {
                                $scope.updSchema(oSchema);
                            }
                        }
                    });
                }
            };
            $scope.recycleSchema = function (oSchema) {
                $scope._appendSchema(oSchema).then(function () {
                    $scope.app.recycleSchemas.splice($scope.app.recycleSchemas.indexOf(oSchema), 1);
                    $scope.update('recycleSchemas');
                });
            };
            $scope.$on('title.xxt.editable.changed', function (e, schema) {
                $scope.updSchema(schema);
            });
            $scope.$on('weight.xxt.editable.changed', function (e, schema) {
                $scope.updSchema(schema, null, 'weight');
            });
            $scope.$on('formula.xxt.editable.changed', function (e, schema) {
                $scope.updSchema(schema, null, 'formula');
            });
            // 回车添加选项
            $('body').on('keyup', function (evt) {
                if (evt.keyCode === 13) {
                    var schemaId, opNode, opIndex;
                    opNode = evt.target.parentNode;
                    if (opNode && opNode.getAttribute('evt-prefix') === 'option') {
                        schemaId = opNode.getAttribute('state');
                        opIndex = parseInt(opNode.dataset.index);
                        $scope.$apply(function () {
                            $scope.addOption($scope.app._schemasById[schemaId], opIndex);
                        });
                    }
                }
            });
            $scope.$on('options.orderChanged', function (e, moved, schemaId) {
                $scope.updSchema($scope.app._schemasById[schemaId]);
            });
            $scope.$on('option.xxt.editable.changed', function (e, op, schemaId) {
                $scope.updSchema($scope.app._schemasById[schemaId]);
            });
            $scope.trustAsHtml = function (schema, prop) {
                return $sce.trustAsHtml(schema[prop]);
            };
        }
    ]);
    /**
     * 单个题目
     */
    ngMod.controller('ctrlSchemaEdit', ['$scope', function ($scope) {
        var _oEditing;
        $scope.editing = _oEditing = {};
        $scope.changeSchemaType = function () {
            var oBeforeState;
            oBeforeState = angular.copy($scope.activeSchema);
            if (false === schemaLib.changeType($scope.activeSchema, _oEditing.type)) {
                _oEditing.type = $scope.activeSchema.type;
                return;
            }
            $scope.activeConfig = wrapLib.input.newWrap($scope.activeSchema).config;
            $scope.updSchema($scope.activeSchema, oBeforeState);
        };
        /*@todo 这部分代码的逻辑有问题*/
        $scope.updSchemaMultiple = function (oUpdatedSchema) {
            !oUpdatedSchema.answer && (oUpdatedSchema.answer = []);
            angular.forEach($scope.answerData, function (data, key) {
                var i = oUpdatedSchema.answer.indexOf(key);
                // 如果key 在answer中 data为false，则去掉
                // 如果不在answer中，data为true ，则添加
                if (i !== -1 && data === false) {
                    oUpdatedSchema.answer.splice(i, 1);
                } else if (i === -1 && data === true) {
                    oUpdatedSchema.answer.push(key);
                }
            });
            $scope.updSchema(oUpdatedSchema);
        };
        $scope.$watch('activeSchema', function () {
            var oActiveSchema, oPage, oWrap;
            $scope.answerData = {};
            $scope.activeConfig = false;
            $scope.inputPage = false;
            if (oActiveSchema = $scope.activeSchema) {
                _oEditing.type = oActiveSchema.type;
                switch (_oEditing.type) {
                    case 'multiple':
                        if (!oActiveSchema.limitChoice) {
                            oActiveSchema.limitChoice = 'N';
                        }
                        if (!oActiveSchema.range) {
                            oActiveSchema.range = [1, oActiveSchema.ops ? oActiveSchema.ops.length : 1];
                        }
                        if (oActiveSchema.answer && angular.isArray(oActiveSchema.answer)) {
                            oActiveSchema.answer.forEach(function (key) {
                                $scope.answerData[key] = true;
                            });
                        }
                        break;
                }
                for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                    oPage = $scope.app.pages[i];
                    if (oPage.type === 'I') {
                        $scope.inputPage = oPage;
                        if (oWrap = oPage.wrapBySchema(oActiveSchema)) {
                            $scope.activeConfig = oWrap.config;
                        }
                        break;
                    }
                }
            }
        });
    }]);
    /**
     * 单个选项
     */
    ngMod.controller('ctrlSchemaOption', ['$scope', function ($scope) {}]);
});