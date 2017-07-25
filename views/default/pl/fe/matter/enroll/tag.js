define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTag',['$scope', '$http', 'srvEnrollApp', 'noticebox', function($scope, $http, srvEnrollApp, noticebox){
        var page, oPage;
        $scope.scopeNames = {
            'S': '参与者',
            'P': '发起人',
            'O': '管理者'
        };
        $scope.oPage = $scope.page = page = oPage = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.createTag = function() {
            var newTags;
            if ($scope.newtag) {
                newTags = $scope.newtag.replace(/\s/, ',');
                newTags = newTags.split(',');
                $http.post('/rest/pl/fe/matter/enroll/tag/create?app=' + $scope.app.id, newTags).then(function(rsp) {
                    rsp.data.data.forEach(function(oNewTag) {
                        $scope.tags.push(oNewTag);
                    });
                });
                $scope.newtag = '';
            }
        };
        $scope.doSearch = function() {
            $http.get('/rest/pl/fe/matter/enroll/tag/get?app=' + $scope.app.id + page.j()).then(function(rsp) {
                $scope.tags = rsp.data.data.tags;
                $scope.page.total = rsp.data.data.total;
            });
        };
        $scope.list4Schema = function(event, tag) {
            event.preventDefault();
            event.stopPropagation();
            var url;
            url = '/rest/pl/fe/matter/enroll/data/list4Schema?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            url += '&page=' + oPage.at + '&size=' + oPage.size;
            $http.post(url, {tag:tag.id}).then(function(result) {
                oPage.total = result.data.total;
                result.data.records.forEach(function(oRecord) {
                    if (oRecord.tag) {
                        oRecord.tag.forEach(function(index, tagId) {
                            if (oApp._tagsById[index]) {
                                oRecord.tag[tagId] = oApp._tagsById[index];
                            }
                        });
                    }
                });
                $scope.records = result.data.records;
            });
        };
        $scope.removeTag = function(tag) {
            if(window.confirm('确认删除')) {
                $http.get('/rest/pl/fe/matter/enroll/tag/remove?tag=' + tag.id).then(function(rsp) {
                    var i = $scope.tags.indexOf(tag);
                    $scope.tags.splice(i, 1);
                    $scope.page.total = $scope.page.total - 1;
                });
            }
        };
        $scope.modify = function(tag) {
            $scope.update(tag, {label:tag.label});
        }
        $scope.upTag = function(tag, index) {
            if (index === 0) return;
            $scope.tags.splice(index, 1);
            $scope.tags.splice(--index, 0, tag);
            $scope.update(tag, {seq: 'U'});
        };
        $scope.downTag = function(tag, index) {
            if (index === $scope.tags.length - 1)  return;
            $scope.tags.splice(index, 1);
            $scope.tags.splice(++index, 0, tag);
            $scope.update(tag, {seq: 'D'});
        };
        $scope.update = function(tag, args) {
            $http.post('/rest/pl/fe/matter/enroll/tag/update?tag=' + tag.id, args).then(function(rsp) {
                noticebox.success('完成保存');
            });
        }
        srvEnrollApp.get().then(function(app) {
            $scope.app = app;
            $scope.doSearch();
        });
    }]);
});
