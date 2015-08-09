xxtApp.filter('transState', function () {
    return function (input) {
        var out = "";
        input = parseInt(input);
        switch (input) {
            case 0:
                out = '未审核';
                break;
            case 1:
                out = '审核通过';
                break;
            case 2:
                out = '审核未通过';
                break;

        }
        return out;
    }
}).controller('wallCtrl', ['$scope', '$http', '$location', 'http2', function ($scope, $http, $location, http2) {
    $scope.wid = $location.search().wid;
    $scope.subPage = 'setting';
    $scope.back = function () {
        location.href = '/rest/mp/app/wall';
    };
    $scope.$watch('subPage', function (nv) {
        $scope.$broadcast('changeSubPage');
    });
    http2.get('/rest/mp/mpaccount/get', function (rsp) {
        $scope.mpaccount = rsp.data;
        http2.get('/rest/mp/app/wall/get?wid=' + $scope.wid, function (rsp) {
            $scope.wall = rsp.data;
        });
    });
}]).controller('settingCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.update = function (name) {
        var nv = {};
        nv[name] = $scope.wall[name];
        http2.post('/rest/mp/app/wall/update?wid=' + $scope.wid, nv);
    };
    $scope.setPic = function () {
        var options = {
            callback: function (url) {
                $scope.wall.pic = url + '?_=' + (new Date()) * 1;
                $scope.update('pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function () {
        $scope.wall.pic = '';
        $scope.update('pic');
    };
    $scope.start = function () {
        $scope.wall.active = 'Y';
        $scope.update('active');
    };
    $scope.end = function () {
        $scope.wall.active = 'N';
        $scope.update('active');
    };
}]).controller('ApproveCtrl', ['$scope', 'http2', function ($scope, http2) {
    var inlist = function (id) {
        for (var i in $scope.messages) {
            if ($scope.messages[i].id == id)
                return true;
        }
        return false;
    };
    $scope.messages = [];
    var worker = new Worker('/views/default/mp/app/wall/wallMessages.js?_=1');
    worker.onmessage = function (event) {
        for (var i in event.data) {
            for (var i in event.data) {
                if (!inlist(event.data[i].id))
                    $scope.messages.splice(0, 0, event.data[i]);
            }
        }
        $scope.$apply();
    };
    worker.postMessage({ wid: $scope.wid, last: 0 });
    $scope.approve = function (msg) {
        http2.get('/rest/mp/app/wall/approve?wid=' + $scope.wid + '&id=' + msg.id, function (rsp) {
            var i = $scope.messages.indexOf(msg);
            $scope.messages.splice(i, 1);
        });
    };
    $scope.reject = function (msg) {
        http2.get('/rest/mp/app/wall/reject?wid=' + $scope.wid + '&id=' + msg.id, function (rsp) {
            var i = $scope.messages.indexOf(msg);
            $scope.messages.splice(i, 1);
        });
    };
    $scope.$on('changeSubPage', function () {
        worker.terminate();
    });
}]).controller('usersCtrl', ['$rootScope', '$scope', 'http2', function ($rootScope, $scope, http2) {
    $scope.doSearch = function () {
        http2.get('/rest/mp/app/wall/users?wid=' + $scope.wid, function (rsp) {
            $scope.users = rsp.data;
        });
    };
    $scope.quitWall = function () {
        var vcode;
        vcode = prompt('是否要退出所有在线用户？，若是，请输入讨论组名称。');
        if (vcode === $scope.wall.title) {
            http2.get('/rest/mp/app/wall/quitWall?wid=' + $scope.wid, function (rsp) {
                $scope.users = null;
                $rootScope.infomsg = '操作完成';
            });
        }
    };
    $scope.doSearch();
}]).controller('msgCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.page = { at: 1, size: 30 };
    $scope.doSearch = function (page) {
        if (!page)
            page = $scope.page.at;
        else
            $scope.page.at = page;
        var url = '/rest/mp/app/wall/messages';
        url += '?wid=' + $scope.wid;
        url += '&page=' + page + '&size=' + $scope.page.size + '&contain=total';
        http2.get(url, function (rsp) {
            $scope.messages = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.resetWall = function () {
        var vcode;
        vcode = prompt('是否要删除讨论组收到的所有信息？，若是，请输入讨论组名称。');
        if (vcode === $scope.wall.title) {
            http2.get('/rest/mp/app/wall/resetWall?wid=' + $scope.wid, function (rsp) {
                $scope.messages = [];
                $scope.page.total = 0;
                $scope.page.at = 1;
            });
        }
    };
    $scope.doSearch();
}]);
