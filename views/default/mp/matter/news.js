xxtApp.controller('newsCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.matterTypes = [
        { value: 'article', title: '单图文', url: '/rest/mp/matter' },
        { value: 'link', title: '链接', url: '/rest/mp/matter' },
        { value: 'enroll', title: '通用活动', url: '/rest/mp/matter' },
        { value: 'lottery', title: '抽奖活动', url: '/rest/mp/matter' },
        { value: 'wall', title: '讨论组', url: '/rest/mp/matter' },
    ];
    var updateMatters = function () {
        http2.post('/rest/mp/matter/news/updateStuff?id=' + $scope.editing.id, $scope.editing.stuffs);
    };
    var editNews = function (news) {
        if (news.url === undefined)
            news.url = 'http://' + location.host + '/rest/mi/matter?mpid=' + $scope.mpid + '&id=' + news.id + '&type=news';
        $scope.editing = news;
    };
    $scope.create = function () {
        $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
            var lean = [];
            for (var i = 0, l = aSelected.length, s; i < l; i++) {
                s = aSelected[i];
                lean.push({ id: s.id, type: matterType });
            }
            http2.post('/rest/mp/matter/news/create', { stuffs: lean }, function (rsp) {
                var news = rsp.data;
                $scope.news.splice(0, 0, news);
                $scope.edit(news);
            });
        });
    };
    $scope.update = function (prop) {
        var nv = {};
        nv[prop] = $scope.editing[prop];
        http2.post('/rest/mp/matter/news/update?id=' + $scope.editing.id, nv);
    };
    $scope.assign = function () {
        $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
            for (var i in aSelected) {
                aSelected[i].type = matterType;
            }
            $scope.editing.stuffs = $scope.editing.stuffs.concat(aSelected);
            updateMatters();
        });
    };
    $scope.edit = function (news) {
        if (news.cascade === undefined) {
            http2.get('/rest/mp/matter/news/cascade?id=' + news.id, function (rsp) {
                news.stuffs = rsp.data.stuffs;
                news.acl = rsp.data.acl;
                editNews(news);
            });
        }
        editNews(news);
    };
    $scope.removeOne = function (event, index) {
        event.preventDefault();
        event.stopPropagation();
        var old = $scope.news[index];
        http2.get('/rest/mp/matter/news/delete?id=' + old.id, function (rsp) {
            $scope.news.splice(index, 1);
            if (index == $scope.news.length)
                $scope.edit($scope.news[index - 1]);
            else
                $scope.edit($scope.news[index]);
        });
    };
    $scope.removeStuff = function (index) {
        $scope.editing.stuffs.splice(index, 1);
        updateMatters();
    };
    $scope.setEmptyReply = function () {
        $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
            if (aSelected.length === 1) {
                var p = { mt: matterType, mid: aSelected[0].id };
                http2.post('/rest/mp/matter/news/setEmptyReply?id=' + $scope.editing.id, p, function (rsp) {
                    $scope.editing.emptyReply = aSelected[0];
                });
            }
        });
    };
    $scope.removeEmptyReply = function () {
        var p = { mt: '', mid: '' };
        http2.post('/rest/mp/matter/news/setEmptyReply?id=' + $scope.editing.id, p, function (rsp) {
            $scope.editing.emptyReply = null;
        });
    };
    $scope.$on('my-sorted', function (ev, val) {
        // rearrange $scope.items
        $scope.editing.stuffs.splice(val.to, 0, $scope.editing.stuffs.splice(val.from, 1)[0]);
        for (var i = 0; i < $scope.editing.stuffs.length; i++) {
            $scope.editing.stuffs.seq = i;
        }
        updateMatters();
    });
    // load news
    $scope.doSearch = function () {
        var url = '/rest/mp/matter/news/get?cascade=n', params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function (rsp) {
            $scope.news = rsp.data;
            if ($scope.news.length > 0)
                $scope.edit($scope.news[0]);
        });
    };
    http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function (rsp) {
        $scope.features = rsp.data;
    });
    $scope.doSearch();
}]);
xxtApp.directive('sortable', function () {
    return {
        link: function (scope, el, attrs) {
            el.sortable({
                revert: 50
            });
            el.disableSelection();
            el.on("sortdeactivate", function (event, ui) {
                var from = angular.element(ui.item).scope().$index;
                var to = el.children('li').index(ui.item);
                if (to >= 0) {
                    scope.$apply(function () {
                        if (from >= 0) {
                            scope.$emit('my-sorted', { from: from, to: to });
                        }
                    });
                }
            });
        }
    };
});
