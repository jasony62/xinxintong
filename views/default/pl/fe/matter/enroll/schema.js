define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 题目管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'srvEnrollPage', function($scope, srvEnrollPage) {
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