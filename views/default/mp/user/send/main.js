xxtApp.controller('sendCtrl', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
    $scope.matterType = 'text';
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.userSet = [];
    $scope.selectMatter = function(matter) {
        $scope.selectedMatter = matter;
    };
    $scope.fetchMatter = function(page) {
        $scope.selectedMatter = null;
        var url = '/rest/mp/matter/' + $scope.matterType,
            params = {};;
        !page && (page = $scope.page.at);
        url += '/list?page=' + page + '&size=' + $scope.page.size;
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            if ('article' === $scope.matterType) {
                $scope.matters = rsp.data.articles;
                $scope.page.total = rsp.data.total;
            } else
                $scope.matters = rsp.data;
        });
    };
    $scope.send = function(evt) {
        var data = {
            id: $scope.selectedMatter.id,
            type: $scope.matterType,
            targetUser: $scope.targetUser,
            userSet: $scope.userSet,
        };
        if ($scope.targetUser === 'M' && $scope.mpa.mpsrc === 'yx') {
            var countOfUsers;
            var doSend = function(phase) {
                http2.get('/rest/mp/send/yxmember?phase=' + phase, function(rsp) {
                    if (rsp.data.nextPhase) {
                        doSend(rsp.data.nextPhase);
                        $scope.$root.progmsg = '正在发送数据，剩余用户：' + rsp.data.countOfOpenids;
                    } else {
                        var msg;
                        msg = '完成向【' + countOfUsers + '】个用户发送';
                        if (rsp.data.length) {
                            msg += '，失败【' + JSON.stringify(rsp.data) + '】用户'
                        }
                        $scope.$root.progmsg = msg;
                        $scope.massStatus = {
                            result: 'ok'
                        };
                    }
                });
            }
            http2.post('/rest/mp/send/yxmember', data, function(rsp) {
                if (rsp.data.nextPhase) {
                    doSend(rsp.data.nextPhase);
                    countOfUsers = rsp.data.countOfOpenids;
                    $scope.$root.progmsg = '正在发送数据，剩余用户：' + countOfUsers;
                } else {
                    $scope.$root.progmsg = '完成向【' + countOfUsers + '】个用户发送';
                    if (rsp.data.length) {
                        $scope.$root.progmsg += '，失败【' + JSON.stringfiy(rsp.data) + '】用户'
                    }
                    $scope.massStatus = {
                        result: 'ok'
                    };
                }
            });
        } else {
            http2.post('/rest/mp/send/mass', data, function(rsp) {
                $scope.$root.infomsg = '发送完成';
                $scope.massStatus = {
                    result: 'ok'
                };
            });
        }
    };
    $scope.openUserPicker = function() {
        $uibModal.open({
            templateUrl: 'userPicker.html',
            controller: 'SendMatterController',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(data) {
            $scope.userSet = data.userSet;
            if ($scope.userSet.length && $scope.userSet[0].identity === -1) {
                http2.get('/rest/mp/analyze/massmsg', function(rsp) {
                    if (rsp.data.length) {
                        $scope.massStatus = rsp.data[0];
                    }
                });
            }
            $scope.targetUser = data.targetUser;
        });
    };
    $scope.removeUserSet = function(us, index) {
        if (us.identity === -1) {
            $scope.massStatus = null;
        }
        $scope.userSet.splice(index, 1);
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpa = rsp.data;
        $scope.hasParent = ($scope.mpa.parent_mpid && $scope.mpa.parent_mpid.length) ? 'Y' : 'N';
    });
    $scope.fetchMatter();
}]);
xxtApp.controller('SendMatterController', ['$scope', '$uibModalInstance', 'userSetAsParam', function($scope, $mi, userSetAsParam) {
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