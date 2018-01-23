define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTaskDetail', ['$scope', 'http2', '$q', 'noticebox', 'srvRecordConverter', 'srvPlanApp', 'srvPlanRecord', '$uibModal', function($scope, http2, $q, noticebox, srvRecordConverter, srvPlanApp, srvPlanRecord, $uibModal) {
        function doTask(seq) {
            var task = _oTasksOfBeforeSubmit[seq];
            task().then(function(rsp) {
                seq++;
                seq < _oTasksOfBeforeSubmit.length ? doTask(seq) : doSave();
            });
        }

        function doSave (){
            //oRecord 原始数据
            //updated 上传数据包
            var updated = {},
                url = '/rest/pl/fe/matter/plan/task/update' + location.search;
            updated.data = _oTask.data;
            updated.supplement = _oTask.supplement;
            http2.post(url, updated, function(rsp) {
                noticebox.success('完成保存');
            });
        }

        $scope.chooseImage = function(action, schema) {
            var data = _oTask.data;
            srvPlanRecord.chooseImage(schema.id).then(function(img) {
                !data[action.id][schema.id] && (data[action.id][schema.id] = []);
                data[action.id][schema.id].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
        };
        $scope.chooseFile = function(action, schema) {
            var r, onSubmit;
            r = new Resumable({
                target: '/rest/site/fe/matter/enroll/record/uploadFile?site=' + _oTask.siteid + '&app=' + _oTask.id,
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
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            var data = _oTask.data;
            var ele = document.createElement('input');
            ele.setAttribute('type', 'file');
            ele.addEventListener('change', function(evt) {
                var i, cnt, f;
                cnt = evt.target.files.length;
                for (i = 0; i < cnt; i++) {
                    f = evt.target.files[i];
                    r.addFile(f);
                    $scope.$apply(function() {
                        data[action.id] === undefined && (data[action.id] = {});
                        data[action.id][schema.id] === undefined && (data[action.id][schema.id] = []);
                        data[action.id][schema.id].push({
                            uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    });
                }
                ele = null;
            }, true);
            ele.click();
        };
        $scope.removeFile = function(field, index) {
            field.splice(index, 1);
        };
        $scope.beforeSubmit = function(fn) {
            if (_oTasksOfBeforeSubmit.indexOf(fn) === -1) {
                _oTasksOfBeforeSubmit.push(fn);
            }
        };
        var _oTask, _oUpdated, _oTasksOfBeforeSubmit;
        _oTasksOfBeforeSubmit = [];

        // 更新的任务数据
        _oUpdated = {};
        $scope.modified = false;
        $scope.updateTask = function(prop) {
            $scope.modified = true;
            _oUpdated[prop] = _oTask[prop];
        };
        $scope.saveTask = function() {
            http2.post('/rest/pl/fe/matter/plan/task/update' + location.search, _oUpdated, function(rsp) {
                $scope.modified = false;
            });
        };
        $scope.saveData = function() {
            _oTasksOfBeforeSubmit.length ? doTask(0) : doSave();
        };
        srvPlanApp.get().then(function(oApp) {
            http2.get('/rest/pl/fe/matter/plan/task/get' + location.search, function(rsp) {
                $scope.task = _oTask = rsp.data;
                $scope.data = _oTask.data;
                $scope.supplement = _oTask.supplement;
                _oTask.taskSchema.actions.forEach(function(oAction) {
                    if (oApp.checkSchemas && oApp.checkSchemas.length) {
                        oAction.checkSchemas = [].concat(oApp.checkSchemas, oAction.checkSchemas);
                    }
                    oAction.checkSchemas.forEach(function(oSchema) {
                        srvRecordConverter.forEdit(oSchema, _oTask.data[oAction.id]);
                    });
                });
            });
        });
    }])
});