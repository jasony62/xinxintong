xxtApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/rest/app/contribute/initiate/article', {
        templateUrl: '/views/default/app/contribute/initiate/edit.html',
        controller: 'editCtrl',
    }).when('/rest/app/contribute/initiate/reviewlog', {
        templateUrl: '/views/default/app/contribute/initiate/reviewlog.html',
        controller: 'reviewlogCtrl',
    });
}]);
xxtApp.controller('initiateCtrl', ['$scope', '$location', '$modal', 'http2', 'Article', function ($scope, $location, $modal, http2, Article) {
    $scope.subView = '';
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.mpid, $scope.entry);
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
    });
    $scope.back = function (event) {
        event.preventDefault();
        location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
    };
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            $scope.fid = params.fid;
            $scope.needReview = params.needReview;
            if ($scope.needReview === 'Y')
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.mpid;
            else
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.fid;
        }
    });
}]);
xxtApp.controller('editCtrl', ['$scope', '$modal', 'http2', 'Article', function ($scope, $modal, http2, Article) {
    $scope.$parent.subView = 'edit';
    $scope.edit = function (event, article) {
        if (article._cascade === true)
            $scope.editing = article;
        else
            $scope.Article.get(article.id).then(function (rsp) {
                article._cascade = true;
                article.channels = rsp.channels;
                $scope.editing = article;
            });
    };
    $scope.setPic = function () {
        $scope.$broadcast('picgallery.open', function (url) {
            var t = (new Date()).getTime();
            url += '?_=' + t;
            $scope.editing.pic = url;
            $scope.Article.update($scope.editing, 'pic');
        }, false);
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.Article.update($scope.editing, 'pic');
    };
    window.onbeforeunload = function (e) {
        var message;
        if ($scope.bodyModified) {
            message = '已经修改的正文还没有保存',
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.onBodyChange = function () {
        $scope.bodyModified = true;
    };
    $scope.$on('tinymce.multipleimage.open', function (event, callback) {
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
    $scope.update = function (name) {
        $scope.Article.update($scope.editing, name);
        name === 'body' && ($scope.bodyModified = false);
    };
    $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.editing.tags) {
                if (aSelected[i].title === $scope.editing.tags[j].title) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, aNewTags, function (rsp) {
            $scope.editing.tags = $scope.editing.tags.concat(aNewTags);
        });
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag) {
        var oNewTag = { title: newTag };
        http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, [oNewTag], function (rsp) {
            $scope.editing.tags.push(oNewTag);
        });
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed) {
        http2.post('/rest/mp/matter/article/removeTag?id=' + $scope.id, [removed], function (rsp) {
            $scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    http2.get('/rest/mp/matter/tag?resType=article', function (rsp) {
        $scope.tags = rsp.data;
    });
    $scope.finish = function () {
        $scope.editing.finished = 'Y';
        $scope.Article.update($scope.editing, 'finished');
    };
    $scope.remove = function () {
        if (window.confirm('确认删除？')) {
            $scope.Article.remove($scope.editing).then(function (data) {
                location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
            });
        }
    };
    $scope.forward = function () {
        $modal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'ReviewUserPickerCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (data) {
            $scope.Article.forward($scope.editing, data, 'R').then(function () {
                location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
            });
        });
    };
}]);
xxtApp.controller('reviewlogCtrl', ['$scope', '$modal', 'http2', 'Reviewlog', function ($scope, $modal, http2, Reviewlog) {
    $scope.$parent.subView = 'reviewlog';
    $scope.Reviewlog = new Reviewlog('initiate', $scope.mpid, { type: 'article', id: $scope.id });
    $scope.Reviewlog.list().then(function (data) {
        $scope.logs = data;
    });
}]);
