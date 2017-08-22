define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 题目管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'srvEnrollPage', 'srvEnrollApp', function($scope, srvEnrollPage, srvEnrollApp) {
        $scope._submitChange = function(changedPages) {
            srvEnrollApp.update('data_schemas').then(function() {
                changedPages.forEach(function(oPage) {
                    srvEnrollPage.update(oPage, ['data_schemas', 'html']);
                });
            });
        };
        $scope.importByOther = function() {
            srvEnrollApp.importSchemaByOther().then(function(schemas) {
                schemas.forEach(function(schema) {
                    var newSchema;
                    newSchema = schemaLib.newSchema(schema.type, $scope.app);
                    newSchema.type === 'member' && (newSchema.schema_id = schema.schema_id);
                    newSchema.title = schema.title;
                    if (schema.ops) {
                        newSchema.ops = schema.ops;
                    }
                    if (schema.range) {
                        newSchema.range = schema.range;
                    }
                    if (schema.count) {
                        newSchema.count = schema.count;
                    }
                    $scope._appendSchema(newSchema);
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