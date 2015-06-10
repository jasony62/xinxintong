xxtApp.controller('myArticleCtrl', ['$location', '$scope', '$modal', 'Article', function ($location, $scope, $modal, Article) {
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
    $scope.return = function () {
        $scope.Article.return($scope.editing).then(function () {
            location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
        });
    };
    $scope.forward = function () {
        $modal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'ReviewUserPickerCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (data) {
            $scope.Article.forward($scope.editing, data, 'T').then(function () {
                location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('review', $scope.mpid, '');
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
}]);
