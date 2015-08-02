angular.module('xxt', []).config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', function ($scope, $location, $http, $q) {
    var mpid, newsId, shareby;
    mpid = $location.search().mpid;
    newsId = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';

    var getNews = function () {
        var deferred = $q.defer();
        $http.get('/rest/mi/news/get?mpid=' + mpid + '&id=' + newsId).success(function (rsp) {
            if (rsp.data.matters && rsp.data.matters.length === 1) {
                $http.get('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + newsId + '&type=news&title=' + rsp.data.title + '&shareby=' + shareby);
                location.href = rsp.data.matters[0].url;
            } else {
                $scope.news = rsp.data;
                deferred.resolve();
                $http.get('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + newsId + '&type=news&title=' + $scope.news.title + '&shareby=' + shareby);
            }
        }).error(function (content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmAuth');
                el.onload = function () { this.height = document.documentElement.clientHeight; };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function () {
                        el.style.display = 'none';
                        getNews().then(function () { $scope.loading = false; });
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
            } else {
                alert(content);
            }
        });
        return deferred.promise;
    };
    $scope.open = function (opened) {
        location.href = opened.url;
    };
    $scope.loading = true;
    getNews().then(function () { $scope.loading = false; });
}]);
