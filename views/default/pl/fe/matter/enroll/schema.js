define(['frame'], function(ngApp) {
    'use strict';
    /**
     * 题目管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', function($scope) {}]);
    /**
     * 导出模板，导入记录
     */
    ngApp.provider.controller('ctrlImport', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
        $scope.importByExcel = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/importByExcel.html?_=1',
                controller: ['$scope', '$timeout', '$uibModalInstance', 'srvEnrollRound', function($scope2, $timeout, $mi, srvEnlRnd) {
                    var oApp, oResu, oOptions;
                    oApp = $scope.app;
                    oResu = new Resumable({
                        target: '/rest/pl/fe/matter/enroll/import/upload?site=' + oApp.siteid + '&app=' + oApp.id,
                        testChunks: false,
                    });
                    oResu.on('fileAdded', function(file, event) {
                        $scope.$apply(function() {
                            noticebox.progress('开始上传文件');
                        });
                        oResu.upload();
                    });
                    oResu.on('progress', function(file, event) {
                        $scope.$apply(function() {
                            noticebox.progress('正在上传文件：' + Math.floor(oResu.progress() * 100) + '%');
                        });
                    });
                    oResu.on('complete', function() {
                        var f, lastModified, oPosted;
                        f = oResu.files.pop().file;
                        lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                        oPosted = {
                            file: {
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                lastModified: lastModified,
                                uniqueIdentifier: f.uniqueIdentifier
                            },
                            options: oOptions
                        };
                        http2.post('/rest/pl/fe/matter/enroll/import/endUpload?site=' + oApp.siteid + '&app=' + oApp.id, oPosted, function success(rsp) {});
                    });
                    $timeout(function() {
                        oResu.assignBrowse(document.getElementById('btnImportByExcel'));
                    });
                    $scope2.dataSchemas = oApp.dataSchemas;
                    $scope2.options = oOptions = {
                        overwrite: '',
                        assoc: {
                            userid: true,
                            source: 'app.mschema'
                        },
                    };
                    $scope2.hasError = true; // 参数是否完整
                    $scope2.intersectedValidNum = 0;
                    srvEnlRnd.list().then(function(oResult) {
                        $scope2.rounds = oResult.rounds;
                    });
                    $scope2.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                    $scope2.$watch('options', function(nv) {
                        $scope2.hasError = false;
                        $scope2.intersectedValidNum = 0;
                        if (!oOptions.rid) {
                            $scope2.hasError = true;
                        }
                        if (oOptions.assoc.userid) {
                            if (!oOptions.assoc.intersected || Object.keys(oOptions.assoc.intersected).length === 0) {
                                $scope2.hasError = true;
                            } else {
                                angular.forEach(oOptions.assoc.intersected, function(matched) {
                                    if (matched) $scope2.intersectedValidNum++;
                                });
                                if ($scope2.intersectedValidNum === 0) $scope2.hasError = true;
                            }
                        }
                    }, true);
                }],
                windowClass: 'auto-height',
                backdrop: 'static',
                size: 'lg'
            }).result.then(function() {

            });
        };
        $scope.downloadTemplate = function() {
            var url = '/rest/pl/fe/matter/enroll/import/downloadTemplate?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            window.open(url);
        };
    }]);
});