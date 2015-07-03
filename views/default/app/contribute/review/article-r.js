xxtApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/rest/app/contribute/review/article', {
        templateUrl: '/views/default/app/contribute/review/edit-r.html',
        controller: 'editCtrl',
    }).when('/rest/app/contribute/review/reviewlog', {
        templateUrl: '/views/default/app/contribute/review/reviewlog.html',
        controller: 'reviewlogCtrl',
    });
}]);
xxtApp.controller('reviewCtrl', ['$location', '$scope', '$modal', 'http2', 'Article', function ($location, $scope, $modal, http2, Article) {
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
    $scope.subView = '';
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.Article = new Article('review', $scope.mpid, $scope.entry);
    $scope.back = function (event) {
        event.preventDefault();
        location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
    };
}]);
xxtApp.controller('editCtrl', ['$scope', '$modal', 'http2', function ($scope, $modal, http2) {
    $scope.$parent.subView = 'edit';
    $scope.refuse = function () {

    };
    $scope.return = function () {
        $modal.open({
            templateUrl: 'replyBox.html',
            controller: function ($scope, $modalInstance, http2) {
                $scope.data = { message: '' };
                $scope.close = function () {
                    $modalInstance.dismiss();
                };
                $scope.ok = function () {
                    $modalInstance.close($scope.data);
                };
            },
            backdrop: 'static',
        }).result.then(function (data) {
            $scope.Article.return($scope.editing, data.message).then(function () {
                location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.pass = function () {
        $scope.Article.pass($scope.editing).then(function () {
            location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
        });
    };
    $scope.publish = function () {
        $modal.open({
            templateUrl: '/views/default/app/contribute/publish.html',
            controller: function ($scope, $modalInstance, http2, mpid) {
                $scope.pickMp = function (mp) {
                    !$scope.selected && ($scope.selected = []);
                    if (mp.checked === 'Y')
                        $scope.selected.push(mp);
                    else
                        $scope.selected.splice($scope.childmps.indexOf(mp), 1);
                };
                $scope.cancel = function () {
                    $modalInstance.dismiss();
                };
                $scope.ok = function () {
                    $modalInstance.close($scope.selected);
                };
                http2.get('/rest/app/contribute/typeset/childmps?mpid=' + mpid, function (rsp) {
                    $scope.childmps = rsp.data;
                });
            },
            resolve: {
                mpid: function () { return $scope.mpid; }
            },
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (selectedMps) {
            if (selectedMps && selectedMps.length) {
                var data = {
                    id: $scope.id,
                    type: 'article',
                };
                var i = 0, mps = [];
                for (i; i < selectedMps.length; i++) {
                    mps.push(selectedMps[i].mpid);
                }
                data.mps = mps;
                http2.post('/rest/mp/send/mass2mps', data, function (rsp) {
                    $scope.$root.infomsg = '发送完成';
                });
            }
        });
    };
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
        var ele = document.querySelector('#content');
        if (ele.contentDocument && ele.contentDocument.body)
            ele.contentDocument.body.innerHTML = data.body;
        $scope.Article.mpaccounts().then(function (data) {
            var target_mps2 = [];
            if ($scope.editing.target_mps.indexOf('[') === 0) {
                var mps = JSON.parse($scope.editing.target_mps);
                angular.forEach(data, function (mpa) {
                    mps.indexOf(mpa.id) !== -1 && target_mps2.push(mpa.name);
                });
                $scope.targetMps = target_mps2.join(',');
            }
        });
    });
    http2.get('/rest/mp/matter/tag?resType=article', function (rsp) {
        $scope.tags = rsp.data;
    });
}]);
xxtApp.controller('reviewlogCtrl', ['$scope', '$modal', 'http2', 'Reviewlog', function ($scope, $modal, http2, Reviewlog) {
    $scope.$parent.subView = 'reviewlog';
    $scope.Reviewlog = new Reviewlog('initiate', $scope.mpid, { type: 'article', id: $scope.id });
    $scope.Reviewlog.list().then(function (data) {
        $scope.logs = data;
    });
}]);
