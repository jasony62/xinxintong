define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.service('serTmplmsg', ['$q', 'http2', function($q, http2) {
        var _baseURL = '/rest/pl/fe/matter/tmplmsg',
            _siteId;
        this.setSiteId = function(siteId) {
            _siteId = siteId;
        };
        this.list = function() {
            var defer = $q.defer();
            http2.get(_baseURL + '/list?site=' + _siteId + '&cascaded=Y').then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.create = function() {
            var defer = $q.defer();
            http2.get(_baseURL + '/create?site=' + _siteId + '').then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.update = function(id, data) {
            var defer = $q.defer();
            http2.post(_baseURL + '/update?site=' + _siteId + '&id=' + id, data).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.remove = function(id) {
            var defer = $q.defer();
            http2.get(_baseURL + '/remove?site=' + _siteId + '&id=' + id).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.addParam = function(tmplmsgId) {
            var defer = $q.defer();
            http2.get(_baseURL + '/addParam?site=' + _siteId + '&tid=' + tmplmsgId).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.updateParam = function(paramId, updated) {
            var defer = $q.defer();
            http2.post(_baseURL + '/updateParam?site=platorm&id=' + paramId, updated).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.removeParam = function(removed) {
            var defer = $q.defer();
            http2.get(_baseURL + '/removeParam?site=' + _siteId + '&pid=' + removed.id).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        };
        this.synWx = function() {
            var defer = $q.defer();
            http2.get(_baseURL + '/synTemplateList?site=' + _siteId).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }
    }]);
    ngApp.provider.controller('ctrlTmplmsg', ['$scope', 'serTmplmsg', 'noticebox', function($scope, serTmplmsg, noticebox) {
        serTmplmsg.setSiteId($scope.siteId);
        $scope.create = function() {
            serTmplmsg.create().then(function(data) {
                $scope.tmplmsgs.push(data);
            });
        };
        $scope.edit = function(tmplmsg, i) {
            $scope.editing = tmplmsg;
            $scope.editing.index = i;
        };
        $scope.doSearch = function() {
            serTmplmsg.list().then(function(data) {
                $scope.tmplmsgs = data;
            });
        };
        $scope.synWx = function() {
            serTmplmsg.synWx().then(function(data) {
                $scope.tmplmsgs = data;
                noticebox.success('完成同步');
            })
        };
        $scope.remove = function() {
            serTmplmsg.remove($scope.editing.id).then(function() {
                var i = $scope.tmplmsgs.indexOf($scope.editing);
                $scope.tmplmsgs.splice(i, 1);
                $scope.editing = null;
            });
        };
        $scope.doSearch();
    }]);
    ngApp.provider.controller('ctrlSetting', ['$scope', 'serTmplmsg', function($scope, serTmplmsg) {
        serTmplmsg.setSiteId($scope.siteId);
        $scope.update = function(n) {
            if (!angular.equals($scope.editing, $scope.persisted)) {
                var nv = {};
                nv[n] = $scope.editing[n];
                serTmplmsg.update($scope.editing.id, nv).then(function() {
                    $scope.persisted = angular.copy($scope.editing);
                });
            }
        };

        $scope.addParam = function() {
            serTmplmsg.addParam($scope.editing.id).then(function(data) {
                var oNewParam = {
                    id: data,
                    pname: 'newparam',
                    plabel: ''
                };
                !angular.isArray($scope.editing.params) && ($scope.editing.params = []);
                $scope.editing.params.push(oNewParam);
            });
        };
        $scope.updateParam = function(updated, name) {
            var p = {
                pname: updated.pname,
                plabel: updated.plabel,
            };
            serTmplmsg.updateParam(updated.id, p);
        };
        $scope.removeParam = function(removed) {
            serTmplmsg.removeParam(removed).then(function() {
                var params = $scope.editing.params;
                params.splice(params.indexOf(removed), 1);
            });
        };
    }]);
    ngApp.provider.controller('sendCtrl', ['$rootScope', '$scope', 'http2', '$uibModal', function($rootScope, $scope, http2, $uibModal) {
        $scope.matterTypes = [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter/tmplmsg'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter/tmplmsg'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter/tmplmsg'
        }, ];
        $scope.userSet = [];
        $scope.data = {};
        $scope.matter = null;
        $scope.startUserPicker = function() {
            $uibModal.open({
                templateUrl: 'userPicker.html',
                controller: 'userPickerCtrl',
                backdrop: 'static',
                size: 'lg',
                windowClass: 'auto-height'
            }).result.then(function(data) {
                $scope.userSet = data.userSet;
                $scope.targetUser = data.targetUser;
            });
        };
        $scope.startMatterPicker = function() {
            $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
                if (aSelected.length) {
                    $scope.matter = {};
                    $scope.matter = aSelected[0];
                    $scope.matter.type = matterType;
                }
            });
        };
        $scope.removeMatter = function() {
            $scope.message.matter = null;
        };
        $scope.send = function() {
            var posted, url;
            posted = {
                data: $scope.data,
                url: $scope.url,
                userSet: $scope.userSet
            };
            if ($scope.matter) posted.matter = $scope.matter;
            url = '/rest/mp/send/tmplmsg';
            url += '?tid=' + $scope.editing.id;
            http2.post(url, posted).then(function(rsp) {
                $rootScope.infomsg = '发送完成';
            });
        };
    }]);
    ngApp.provider.controller('logCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.page = {
            at: 1,
            size: 30
        };
        $scope.doSearch = function() {
            var url = '/rest/mp/send/tmplmsglog?tid=' + $scope.editing.id;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url).then(function(rsp) {
                $scope.logs = rsp.data.logs;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.doSearch();
    }])
});