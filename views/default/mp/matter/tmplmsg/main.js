app = xxtApp.controller('tmplmsgCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.back = function() {
        location.href = '/page/mp/matter/tmplmsgs';
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpa = rsp.data;
    });
    http2.get('/rest/mp/matter/tmplmsg/get?id=' + $location.search().id, function(rsp) {
        $scope.editing = rsp.data;
    });
}]);
app.controller('editCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.update = function(n) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var nv = {};
            nv[n] = $scope.editing[n];
            http2.post('/rest/mp/matter/tmplmsg/update?id=' + $scope.editing.id, nv, function(rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.addParam = function() {
        http2.get('/rest/mp/matter/tmplmsg/addParam?tid=' + $scope.editing.id, function(rsp) {
            var oNewParam = {
                id: rsp.data,
                pname: 'newparam',
                plabel: ''
            };
            $scope.editing.params.push(oNewParam);
        });
    };
    $scope.updateParam = function(updated, name) {
        var p = {
            pname: updated.pname,
            plabel: updated.plabel,
        };
        http2.post('/rest/mp/matter/tmplmsg/updateParam?id=' + updated.id, p);
    };
    $scope.removeParam = function(removed) {
        http2.get('/rest/mp/matter/tmplmsg/removeParam?pid=' + removed.id, function(rsp) {
            var i = $scope.editing.params.indexOf(removed);
            $scope.editing.params.splice(i, 1);
        });
    };
}]);
app.controller('sendCtrl', ['$rootScope', '$scope', 'http2', '$uibModal', function($rootScope, $scope, http2, $uibModal) {
    $scope.matterTypes = [{
        value: 'article',
        title: '单图文',
        url: '/rest/mp/matter'
    }, {
        value: 'news',
        title: '多图文',
        url: '/rest/mp/matter'
    }, {
        value: 'channel',
        title: '频道',
        url: '/rest/mp/matter'
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
        http2.post(url, posted, function(rsp) {
            $rootScope.infomsg = '发送完成';
        });
    };
}]);
app.controller('logCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/send/tmplmsglog?tid=' + $scope.editing.id;
        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
        http2.get(url, function(rsp) {
            $scope.logs = rsp.data.logs;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.doSearch();
}])
app.controller('userPickerCtrl', ['$scope', '$uibModalInstance', 'userSetAsParam', function($scope, $mi, userSetAsParam) {
    $scope.userSet = {};
    $scope.cancel = function() {
        $mi.dismiss();
    };
    $scope.ok = function() {
        var data, targetUser;
        targetUser = /authid_\d+/.test($scope.userSet.userScope) ? 'M' : 'F';
        data = {
            targetUser: targetUser
        };
        data.userSet = userSetAsParam.convert($scope.userSet);
        $mi.close(data);
    };
}]);