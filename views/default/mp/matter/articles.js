xxtApp.controller('articleCtrl', ['$scope', '$window', 'http2', function ($scope, $window, http2) {
    var getArticles = function () {
        var options = {
            channel: $scope.selectedChannelsId,
            tag: $scope.selectedTagsId,
            order: $scope.order
        };
        var url = '/rest/mp/matter/article/get?' + $scope.page.toString();
        $scope.fromParent && $scope.fromParent === 'Y' && (options.src = 'p');
        http2.post(url, options, function (rsp) {
            $scope.articles = rsp.data[0];
            rsp.data[1] !== undefined && ($scope.page.total = rsp.data[1]);
        });
    };
    var getInitData = function () {
        http2.get('/rest/mp/matter/tag?resType=article', function (rsp) {
            $scope.tags = rsp.data;
            getArticles();
        });
        http2.get('/rest/mp/matter/channel?cascade=n', function (rsp) {
            $scope.channels = rsp.data;
        });
    };
    $scope.selectedChannels = [];
    $scope.selectedChannelsId = [];
    $scope.selectedTags = [];
    $scope.selectedTagsId = [];
    $scope.order = 'time';
    $scope.page = { at: 1, size: 30, toString: function () { return 'page=' + this.at + '&size=' + this.size; } };
    $scope.create = function () {
        http2.get('/rest/mp/matter/article/create', function (rsp) {
            location.href = '/page/mp/matter/article?id=' + rsp.data.id;
        });
    };
    $scope.edit = function (article) {
        location.href = '/page/mp/matter/article?id=' + article.id;
    };
    $scope.remove = function (event, article, index) {
        event.preventDefault();
        event.stopPropagation();
        if ($window.confirm('确认删除？'))
            http2.get('/rest/mp/matter/article/remove?id=' + article.id, function (rsp) {
                $scope.articles.splice(index, 1);
            });
    };
    $scope.doSearch = function () {
        getArticles();
    };
    $scope.$on('channel.xxt.combox.done', function (event, aSelected) {
        for (var i in aSelected) {
            if ($scope.selectedChannels.indexOf(aSelected[i].title) === -1) {
                $scope.selectedChannels.push(aSelected[i].title);
                $scope.selectedChannelsId.push(aSelected[i].id);
            }
        }
        getArticles();
    });
    $scope.$on('channel.xxt.combox.del', function (event, removed) {
        var i = $scope.selectedChannels.indexOf(removed);
        $scope.selectedChannels.splice(i, 1);
        $scope.selectedChannelsId.splice(i, 1);
        getArticles();
    });
    $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
        for (var i in aSelected) {
            if ($scope.selectedTags.indexOf(aSelected[i].title) === -1) {
                $scope.selectedTags.push(aSelected[i].title);
                $scope.selectedTagsId.push(aSelected[i].id);
            }
        }
        getArticles();
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed) {
        var i = $scope.selectedTags.indexOf(removed);
        $scope.selectedTags.splice(i, 1);
        $scope.selectedTagsId.splice(i, 1);
        getArticles();
    });
    getInitData();
}]);
