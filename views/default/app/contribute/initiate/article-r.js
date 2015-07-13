xxtApp.controller('initiateCtrl', ['$location', '$scope', 'Article', 'Entry', function ($location, $scope, Article, Entry) {
    $scope.subView = '';
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.mpid, '');
    $scope.Entry = new Entry($scope.mpid, $scope.entry);
    $scope.back = function (event) {
        event.preventDefault();
        location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
    };
}]);
xxtApp.controller('editCtrl', ['$scope', '$modal', 'http2', 'Article', function ($scope, $modal, http2, Article) {
    $scope.$parent.subView = 'edit';
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
        var ele = document.querySelector('#content');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.editing.body;
        }
    }).then(function () {
        $scope.Entry.get().then(function (data) {
            var i, j, ch, mapSubChannels = {};
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            for (i = 0, j = data.subChannels.length; i < j; i++) {
                ch = data.subChannels[i];
                mapSubChannels[ch.id] = ch;
            }
            for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                ch = $scope.editing.channels[i];
                mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
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