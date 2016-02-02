xxtApp.controller('newsesCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.matterTypes = [{
        value: 'article',
        title: '单图文',
        url: '/rest/mp/matter'
    }, {
        value: 'link',
        title: '链接',
        url: '/rest/mp/matter'
    }, {
        value: 'enroll',
        title: '通用活动',
        url: '/rest/mp/matter'
    }, {
        value: 'lottery',
        title: '抽奖活动',
        url: '/rest/mp/matter'
    }, {
        value: 'wall',
        title: '讨论组',
        url: '/rest/mp/matter'
    }, ];
    $scope.create = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
            var lean = [];
            for (var i = 0, l = aSelected.length, s; i < l; i++) {
                s = aSelected[i];
                lean.push({
                    id: s.id,
                    type: matterType
                });
            }
            http2.post('/rest/mp/matter/news/create', {
                matters: lean
            }, function(rsp) {
                location.href = '/rest/mp/matter/news?id=' + rsp.data.id;
            });
        });
    };
    $scope.edit = function(news) {
        location.href = '/rest/mp/matter/news?id=' + news.id;
    };
    $scope.removeOne = function(event, news, index) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('确认删除？'))
            http2.get('/rest/mp/matter/news/delete?id=' + news.id, function(rsp) {
                $scope.newses.splice(index, 1);
            });
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/news/list?cascade=N',
            params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.newses = rsp.data;
        });
    };
    http2.get('/rest/mp/feature/get?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    $scope.doSearch();
}]);