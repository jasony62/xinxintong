ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/site/fe/matter/contribute/initiate/article', {
        templateUrl: '/views/default/app/contribute/initiate/edit.html',
        controller: 'editCtrl',
    }).when('/rest/site/fe/matter/contribute/initiate/reviewlog', {
        templateUrl: '/views/default/app/contribute/initiate/reviewlog.html',
        controller: 'reviewlogCtrl',
    });
}]);
ngApp.controller('ctrlInitiate', ['$scope', '$location', '$uibModal', 'http2', 'mediagallery', 'Article', 'Entry', 'Reviewlog', function($scope, $location, $uibModal, http2, mediagallery, Article, Entry, Reviewlog) {
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    $scope.siteId = $location.search().site;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.siteId, $scope.entry);
    $scope.Entry = new Entry($scope.siteId, $scope.entry);
    $scope.Article.get($scope.id).then(function(data) {
        var url;
        $scope.editing = data;
        url = 'http://' + location.host + '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + $scope.id + '&type=article';
        $scope.access = {
            url: url,
            qrcode: '/rest/site/fe/matter/contribute/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent(url),
        };
        !$scope.editing.attachments && ($scope.editing.attachments = []);
    }).then(function() {
        $scope.Entry.get().then(function(data) {
            var i, j, ch, mapSubChannels = {},
                picUrl;
            data.params = JSON.parse(data.params);
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            if (data.subChannels)
                for (i = 0, j = data.subChannels.length; i < j; i++) {
                    ch = data.subChannels[i];
                    mapSubChannels[ch.id] = ch;
                }
            if ($scope.editing.channels) {
                for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                    ch = $scope.editing.channels[i];
                    mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
                }
            }
            $scope.picBoxId = data.pic_store_at === 'M' ? $scope.siteId : data.user.uid;
            $scope.needReview = (data.reviewers && data.reviewers.length) ? 'Y' : 'N';
        });
    });
    $scope.back = function(event) {
        event.preventDefault();
        location.href = '/rest/site/fe/matter/contribute/initiate?site=' + $scope.siteId + '&entry=' + $scope.entry;
    };
    $scope.edit = function(event, article) {
        if (article._cascade === true)
            $scope.editing = article;
        else
            $scope.Article.get(article.id).then(function(rsp) {
                article._cascade = true;
                article.channels = rsp.channels;
                $scope.editing = article;
            });
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1;;
                $scope.Article.update($scope.editing, 'pic');
            }
        };
        mediagallery.open($scope.picBoxId, options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.Article.update($scope.editing, 'pic');
    };
    window.onbeforeunload = function(e) {
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
    $scope.onBodyChange = function() {
        $scope.bodyModified = true;
    };
    $scope.tinymceSave = function() {
        $scope.update('body');
    };
    $scope.$on('tinymce.multipleimage.open', function(event, callback) {
        var options = {
            callback: callback,
            multiple: true,
            setshowname: true
        }
        mediagallery.open($scope.picBoxId, options);
    });
    $scope.update = function(name) {
        $scope.Article.update($scope.editing, name);
        name === 'body' && ($scope.bodyModified = false);
    };
    $scope.$on('sub-channel.xxt.combox.done', function(event, aSelected) {
        var i, j, c, params = {
            channels: [],
            matter: {
                id: $scope.editing.id,
                type: 'article'
            }
        };
        for (i = 0, j = aSelected.length; i < j; i++) {
            c = aSelected[i];
            params.channels.push({
                id: c.id
            });
        }
        $scope.Article.addChannels(params).then(function() {
            for (i = 0, j = aSelected.length; i < j; i++) {
                c = aSelected[i];
                $scope.editing.subChannels.push({
                    id: c.id,
                    title: c.title
                });
            }
        });
    });
    $scope.$on('sub-channel.xxt.combox.del', function(event, removed) {
        $scope.Article.delChannel($scope.editing.id, removed.id).then(function() {
            var i = $scope.editing.subChannels.indexOf(removed);
            $scope.editing.subChannels.splice(i, 1);
        });
    });
    var r = new Resumable({
        target: '/rest/site/fe/matter/contribute/attachment/upload?site=' + $scope.siteId + '&articleid=' + $scope.id,
        testChunks: false,
    });
    r.assignBrowse(document.getElementById('addAttachment'));
    r.on('fileAdded', function(file, event) {
        $scope.$root.progmsg = '开始上传文件';
        $scope.$root.$apply('progmsg');
        r.upload();
    });
    r.on('progress', function(file, event) {
        $scope.$root.progmsg = '正在上传文件：' + Math.floor(r.progress() * 100) + '%';
        $scope.$root.$apply('progmsg');
    });
    r.on('complete', function() {
        var f, lastModified, posted;
        f = r.files.pop().file;
        lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
        posted = {
            name: f.name,
            size: f.size,
            type: f.type,
            lastModified: lastModified,
            uniqueIdentifier: f.uniqueIdentifier,
        };
        http2.post('/rest/site/fe/matter/contribute/attachment/add?site=' + $scope.siteId + '&id=' + $scope.id, posted, function success(rsp) {
            $scope.editing.attachments.push(rsp.data);
            $scope.$root.progmsg = null;
        });
    });
    $scope.delAttachment = function(index, att) {
        $scope.$root.progmsg = '删除文件';
        http2.get('/rest/site/fe/matter/contribute/attachment/del?site=' + $scope.siteId + '&id=' + att.id, function success(rsp) {
            $scope.editing.attachments.splice(index, 1);
            $scope.$root.progmsg = null;
        });
    };
    $scope.downloadUrl = function(att) {
        var url;
        url = '/rest/site/fe/matter/article/attachmentGet';
        url += '?site=' + $scope.siteId;
        url += '&articleid=' + $scope.id + '&attachmentid=' + att.id;
        return url;
    };
    $scope.finish = function() {
        if ($scope.entryApp.params.requireSubChannel && $scope.entryApp.params.requireSubChannel === 'Y') {
            if (!$scope.editing.subChannels || $scope.editing.subChannels.length === 0) {
                $scope.errmsg = '请指定投稿频道';
                return;
            }
        }
        if ($scope.bodyModified) {
            $scope.Article.update($scope.editing, 'body').then(function() {
                $scope.editing.finished = 'Y';
                $scope.Article.update($scope.editing, 'finished');
            });
            $scope.bodyModified = false;
        } else {
            $scope.editing.finished = 'Y';
            $scope.Article.update($scope.editing, 'finished');
        }
    };
    $scope.remove = function() {
        if (window.confirm('确认删除？')) {
            $scope.Article.remove($scope.editing).then(function(data) {
                location.href = '/rest/site/fe/matter/contribute/initiate?site=' + $scope.siteId + '&entry=' + $scope.entry;
            });
        }
    };
    $scope.forward = function() {
        if ($scope.bodyModified) {
            $scope.errmsg = '已经修改的正文还没有保存';
            return;
        }
        if ($scope.entryApp.params.requireSubChannel && $scope.entryApp.params.requireSubChannel === 'Y') {
            if (!$scope.editing.subChannels || $scope.editing.subChannels.length === 0) {
                $scope.errmsg = '请指定投稿频道';
                return;
            }
        }
        $uibModal.open({
            templateUrl: 'review-list.html',
            controller: ['$scope', '$uibModalInstance', 'reviewers', function($scope, $mi, reviewers) {
                $scope.reviewers = reviewers;
                $scope.data = {
                    selected: '0'
                };
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $scope.data.selected ? $mi.close(reviewers[$scope.data.selected]) : $mi.dismiss();
                };
            }],
            resolve: {
                reviewers: function() {
                    var level1 = [];
                    angular.forEach($scope.entryApp.reviewers, function(reviewer) {
                        if (reviewer.level === '1') {
                            level1.push(reviewer);
                        }
                    });
                    return level1;
                }
            },
            backdrop: 'static',
        }).result.then(function(who) {
            $scope.Article.forward($scope.editing, who.identity, 'R').then(function() {
                location.href = '/rest/site/fe/matter/contribute/initiate?site=' + $scope.siteId + '&entry=' + $scope.entry;
            });
        });
    };
    $scope.Reviewlog = new Reviewlog('initiate', $scope.siteId, {
        type: 'article',
        id: $scope.id
    });
    $scope.Reviewlog.list().then(function(data) {
        $scope.logs = data;
    });
}]);