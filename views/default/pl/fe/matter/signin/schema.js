define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 填写项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', '$q', 'srvSigninApp', 'srvSigninPage', function($scope, $q, srvSigninApp, srvSigninPage) {
        $scope._submitChange = function(changedPages) {
            var updatedAppProps = ['data_schemas'],
                oSchema, oNicknameSchema, oAppNicknameSchema;

            for (var i = $scope.app.dataSchemas.length - 1; i >= 0; i--) {
                oSchema = $scope.app.dataSchemas[i];
                if (oSchema.required === 'Y') {
                    if (oSchema.type === 'shorttext') {
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
            srvSigninApp.update(updatedAppProps).then(function() {
                changedPages.forEach(function(oPage) {
                    srvSigninPage.update(oPage, ['data_schemas', 'html']);
                });
            });
        };
        $scope.assignEnrollApp = function() {
            srvSigninApp.assignEnrollApp();
        };
        $scope.cancelEnrollApp = function() {
            srvSigninApp.cancelEnrollApp();
        };
        $scope.assignGroupApp = function() {
            srvSigninApp.assignGroupApp();
        };
        $scope.cancelGroupApp = function() {
            $scope.app.group_app_id = '';
            srvSigninApp.update('group_app_id');
        };
        $scope.updConfig = function(oActiveSchema) {
            var pages, oPage;
            pages = $scope.app.pages;
            for (var i = pages.length - 1; i >= 0; i--) {
                oPage = pages[i];
                if (oPage.type === 'I') {
                    oPage.updateSchema(oActiveSchema);
                    srvSigninPage.update(oPage, ['data_schemas', 'html']);
                }
            }
        };
    }]);
});