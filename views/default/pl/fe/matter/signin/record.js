define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvSigninApp', 'srvSigninRecord', function($scope, $uibModal, srvSigninApp, srvSigninRecord) {
        $scope.absent = function() {
            srvSigninRecord.absent($scope.page.byRound ? $scope.page.byRound : null).then(function(data) {
                $scope.absentUsers = data.users;
            });
        };
        $scope.editCause = function(user) {
            srvSigninRecord.editCause(user).then(function(data) {
                user.absent_cause = data;
            });
        }
        $scope.toggleAbsent = function() {
            $scope.category = $scope.category === 'absent' ? 'record' : 'absent';
        };
        $scope.doSearch = function(pageNumber) {
            $scope.rows.reset();
            srvSigninRecord.search(pageNumber);
        };
        $scope.changeRound = function() {
            $scope.doSearch(1);
            $scope.absent();
        };
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.criteria.tags = $scope.criteria.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.criteria.tags.indexOf(removed);
            $scope.criteria.tags.splice(i, 1);
            $scope.doSearch();
        });
        $scope.filter = function() {
            srvSigninRecord.filter().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.editRecord = function(record) {
            srvSigninRecord.editRecord(record);
        };
        $scope.batchTag = function() {
            srvSigninRecord.batchTag($scope.rows);
        };
        $scope.removeRecord = function(record) {
            srvSigninRecord.remove(record);
        };
        $scope.empty = function() {
            srvSigninRecord.empty();
        };
        $scope.batchVerify = function() {
            srvSigninRecord.batchVerify($scope.rows);
        };
        $scope.verifyAll = function() {
            srvSigninRecord.verifyAll();
        };
        $scope.notify = function(isBatch) {
            srvSigninRecord.notify(isBatch ? $scope.rows : undefined);
        };
        $scope.export = function() {
            srvSigninRecord.export();
        };
        $scope.exportImage = function() {
            srvSigninRecord.exportImage();
        };
        $scope.importByEnrollApp = function() {
            srvSigninRecord.importByEnrollApp().then(function(data) {
                if (data && data.length) {
                    $scope.doSearch(1);
                }
            });
        };
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            count: 0,
            change: function(index) {
                this.selected[index] ? this.count++ : this.count--;
            },
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
                this.count = 0;
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.records.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.records.length;
            } else if (checked === 'N') {
                $scope.rows.reset();
            }
        });
        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        $scope.category = 'record';
        $scope.bHasAbsent = false; // 是否有缺席名单
        srvSigninApp.get().then(function(oApp) {
            srvSigninRecord.init(oApp, $scope.page, $scope.criteria, $scope.records);
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            oApp.dataSchemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            oApp._schemasFromEnrollApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    enrollDataSchemas.push(schema);
                }
            });
            $scope.enrollDataSchemas = enrollDataSchemas;
            oApp._schemasFromGroupApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    groupDataSchemas.push(schema);
                }
            });
            $scope.groupDataSchemas = groupDataSchemas;
            $scope.tmsTableWrapReady = 'Y';
            $scope.bRequireNickname = oApp.assignedNickname.valid !== 'Y' || !oApp.assignedNickname.schema;
            $scope.doSearch();
            if (oApp.entryRule.group.id || oApp.entryRule.enroll.id || oApp.entryRule.scope === 'member') {
                $scope.bHasAbsent = true;
                $scope.absent();
            }
        });
    }]);
});