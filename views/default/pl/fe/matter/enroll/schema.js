define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 题目管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'srvEnrollPage', 'srvEnrollApp', function($scope, srvEnrollPage, srvEnrollApp) {
        /**
         * 提交对题目的修改
         * 1、自动查找作为昵称的字段
         */
        $scope._submitChange = function(changedPages) {
            var updatedAppProps = ['data_schemas'],
                oSchema, oNicknameSchema, oAppNicknameSchema;

            for (var i = $scope.app.dataSchemas.length - 1; i >= 0; i--) {
                oSchema = $scope.app.dataSchemas[i];
                if (oSchema.required === 'Y') {
                    if (oSchema.type === 'shorttext' || oSchema.type === 'member') {
                        if (oSchema.title === '姓名') {
                            oNicknameSchema = oSchema;
                            break;
                        }
                        if (oSchema.title.indexOf('姓名') !== -1) {
                            if (oNicknameSchema && oSchema.length < oNicknameSchema.length) {
                                oNicknameSchema = oSchema;
                            }
                        }
                        if (oSchema.format && oSchema.format === 'name') {
                            oNicknameSchema = oSchema;
                        }
                    }
                }
            }
            if (oNicknameSchema) {
                if (oAppNicknameSchema = $scope.app.assignedNickname) {
                    if (oAppNicknameSchema.schema) {
                        if (oAppNicknameSchema.schema.id !== '') {
                            oAppNicknameSchema.schema.id = oNicknameSchema.id;
                            updatedAppProps.push('assignedNickname');
                        }
                    } else {
                        oAppNicknameSchema.valid = 'Y';
                        oAppNicknameSchema.schema = { id: oNicknameSchema.id };
                        updatedAppProps.push('assignedNickname');
                    }
                }
            } else {
                if ($scope.app.assignedNickname.schema) {
                    delete $scope.app.assignedNickname.schema;
                    updatedAppProps.push('assignedNickname');
                }
            }
            srvEnrollApp.update(updatedAppProps).then(function() {
                changedPages.forEach(function(oPage) {
                    srvEnrollPage.update(oPage, ['data_schemas', 'html']);
                });
            });
        };
        $scope.assignEnrollApp = function() {
            srvEnrollApp.assignEnrollApp();
        };
        $scope.cancelEnrollApp = function() {
            $scope.app.enroll_app_id = '';
            srvEnrollApp.update('enroll_app_id');
        };
        $scope.assignGroupApp = function() {
            srvEnrollApp.assignGroupApp();
        };
        $scope.cancelGroupApp = function() {
            $scope.app.group_app_id = '';
            srvEnrollApp.update('group_app_id');
        };
        $scope.updConfig = function(oActiveSchema) {
            var pages, oPage;
            pages = $scope.app.pages;
            for (var i = pages.length - 1; i >= 0; i--) {
                oPage = pages[i];
                if (oPage.type === 'I') {
                    oPage.updateSchema(oActiveSchema);
                    srvEnrollPage.update(oPage, ['data_schemas', 'html']);
                }
            }
        };
    }]);
    /**
     * 导入导出记录
     */
    ngApp.provider.controller('ctrlImport', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnrollApp) {
        srvEnrollApp.get().then(function(app) {
            var r = new Resumable({
                target: '/rest/pl/fe/matter/enroll/import/upload?site=' + app.siteid + '&app=' + app.id,
                testChunks: false,
            });
            r.assignBrowse(document.getElementById('btnImportRecords'));
            r.on('fileAdded', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('开始上传文件');
                });
                r.upload();
            });
            r.on('progress', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('正在上传文件：' + Math.floor(r.progress() * 100) + '%');
                });
            });
            r.on('complete', function() {
                var f, lastModified, posted;
                f = r.files.pop().file;
                lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                posted = {
                    name: f.name,
                    size: f.size,
                    type: f.type,
                    lastModified: lastModified,
                    uniqueIdentifier: f.uniqueIdentifier,
                };
                http2.post('/rest/pl/fe/matter/enroll/import/endUpload?site=' + app.siteid + '&app=' + app.id, posted, function success(rsp) {});
            });
        });
        $scope.options = {
            overwrite: 'Y'
        };
        $scope.downloadTemplate = function() {
            var url = '/rest/pl/fe/matter/enroll/import/downloadTemplate?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            window.open(url);
        };
    }]);
});