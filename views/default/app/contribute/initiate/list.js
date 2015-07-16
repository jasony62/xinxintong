xxtApp.controller('initiateCtrl', ['$scope', '$location', 'Article', 'Entry', function ($scope, $location, Article, Entry) {
    $scope.phases = { 'I': '未送审', 'R': '审核中', 'T': '版面' };
    $scope.approved = { 'Y': '通过', 'N': '未通过' };
    $scope.create = function () {
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            var ele = document.querySelector('#pagePopup iframe'), css, js;
            if (ele.contentDocument && ele.contentDocument.body) {
                ele.contentDocument.body.innerHTML = $scope.entryApp.pageShift2Pc.html;
                css = document.createElement('style');
                css.innerHTML = $scope.entryApp.pageShift2Pc.css;
                ele.contentDocument.body.appendChild(css);
                js = document.createElement('script');
                js.innerHTML = $scope.entryApp.pageShift2Pc.js;
                ele.contentDocument.body.appendChild(js);
            }
            $('#pagePopup').show();
        } else {
            $scope.Article.create().then(function (data) {
                location.href = '/rest/app/contribute/initiate/article?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + data.id;
            });
        }
    };
    $scope.open = function (article) {
        location.href = '/rest/app/contribute/initiate/article?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + article.id;
    };
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.Entry = new Entry($scope.mpid, $scope.entry);
    $scope.Article = new Article('initiate', $scope.mpid, $scope.entry);
    $scope.Entry.get().then(function (data) {
        $scope.entryApp = data;
    });
    $scope.Article.list().then(function (data) {
        $scope.articles = data;
    });
}]);
