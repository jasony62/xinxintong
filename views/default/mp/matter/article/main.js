xxtApp.controller('articleCtrl', ['$scope', '$location', 'http2', function ($scope, $location, http2) {
    $scope.id = $location.search().id;
    $scope.back = function () {
        location.href = '/page/mp/matter/articles';
    };
    http2.get('/rest/mp/matter/article?id=' + $scope.id, function (rsp) {
        $scope.editing = rsp.data;
        $scope.entryUrl = 'http://' + location.host + '/rest/mi/matter?mpid=' + $scope.editing.mpid + '&id=' + $scope.id + '&type=article';
        $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.editing.mpid;
        if (!$scope.editing.creater)
            $scope.bodyEditable = false;
        else
            $scope.bodyEditable = true;
    });
}]);
xxtApp.controller('editCtrl', ['$rootScope', '$scope', 'http2', function ($rootScope, $scope, http2) {
    $scope.innerlinkTypes = [
        { value: 'article', title: '单图文', url: '/rest/mp/matter' },
        { value: 'news', title: '多图文', url: '/rest/mp/matter' },
        { value: 'channel', title: '频道', url: '/rest/mp/matter' }
    ];
    var getInitData = function () {
        http2.get('/rest/mp/matter/tag?resType=article', function (rsp) {
            $scope.tags = rsp.data;
        });
        http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function (rsp) {
            $scope.features = rsp.data;
        });
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
    $scope.update = function (name) {
        var nv = {};
        nv[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
        http2.post('/rest/mp/matter/article/update?id=' + $scope.editing.id, nv, function () {
            name === 'body' && ($scope.bodyModified = false);
        });
    };
    $scope.setPic = function () {
        $scope.$broadcast('picgallery.open', function (url) {
            url += '?_=' + (new Date()).getTime();
            $scope.editing.pic = url;
            $scope.update('pic');
        }, false);
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.delAttachment = function (index, att) {
        $rootScope.progmsg = '删除文件';
        http2.get('/rest/mp/matter/article/attachmentDel?id=' + att.id, function success(rsp) {
            $scope.editing.attachments.splice(index, 1);
            $rootScope.progmsg = null;
        });
    };
    $scope.downloadUrl = function (att) {
        return '/rest/mi/matter/articleAttachment?mpid=' + $scope.mpid + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
    };
    $scope.gotoCode = function () {
        if ($scope.editing.page_id != 0)
            location.href = '/rest/code?pid=' + $scope.editing.page_id;
        else {
            http2.get('/rest/code/create', function (rsp) {
                var nv = { 'page_id': rsp.data.id };
                http2.post('/rest/mp/matter/article/update?id=' + $scope.editing.id, nv, function () {
                    $scope.editing.page_id = rsp.data.id;
                    location.href = '/rest/code?pid=' + rsp.data.id;
                });
            });
        }
    };
    $scope.embedMatter = function () {
        $scope.$broadcast('mattersgallery.open', function (matters, type) {
            var editor, dom, i, matter, mtype, fn;
            editor = tinymce.get('body1');
            dom = editor.dom;
            for (i = 0; i < matters.length; i++) {
                matter = matters[i];
                mtype = matter.type ? matter.type : type;
                fn = "openMatter(" + matter.id + ",'" + mtype + "')";
                console.log('fn', fn);
                editor.insertContent(dom.createHTML('p', { 'class': 'matter-link' }, dom.createHTML('a', {
                    href: '#',
                    "onclick": fn,
                }, dom.encode(matter.title))));
            }
        });
    };
    $scope.$on('tinymce.multipleimage.open', function (event, callback) {
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
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
        http2.post('/rest/mp/matter/article/removeTag?id=' + $scope.editing.id, [removed], function (rsp) {
            $scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    getInitData();
    var r = new Resumable({
        target: '/rest/mp/matter/article/upload?articleid=' + $scope.id,
        testChunks: false,
    });
    r.assignBrowse(document.getElementById('addAttachment'));
    r.on('fileAdded', function (file, event) {
        console.log('fileAdded.');
        $rootScope.progmsg = '开始上传文件';
        $rootScope.$apply('progmsg');
        r.upload();
    });
    r.on('progress', function (file, event) {
        console.log('progress.');
        $rootScope.progmsg = '正在上传文件：' + Math.floor(r.progress() * 100) + '%';
        $rootScope.$apply('progmsg');
    });
    r.on('complete', function () {
        console.log('complete.');
        var f, lastModified, posted;
        f = r.files.pop().file;
        lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
        posted = { name: f.name, size: f.size, type: f.type, lastModified: lastModified };
        http2.post('/rest/mp/matter/article/attachmentAdd?id=' + $scope.id, posted, function success(rsp) {
            $scope.editing.attachments.push(rsp.data);
            $rootScope.progmsg = null;
        });
    });
}]);
xxtApp.controller('RemarkCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.page = { current: 1, size: 30 };
    $scope.delRemark = function (remark, index) {
        var ret = window.prompt('删除当前评论吗？请输入文章的标题');
        if (ret === $scope.editing.title) {
            http2.get('/rest/mp/matter/article/delRemark?id=' + remark.id, function (rsp) {
                $scope.remarks.splice(index, 1);
            });
        }
    };
    $scope.cleanRemark = function () {
        var ret = window.prompt('删除当前评论吗？请输入文章的标题');
        if (ret === $scope.editing.title) {
            http2.get('/rest/mp/matter/article/cleanRemark?articleid=' + $scope.id, function (rsp) {
                $scope.remarks = [];
            });
        }
    };
    $scope.doSearch = function () {
        var page = 'page=' + $scope.page.current + '&size=' + $scope.page.size;
        http2.get('/rest/mp/matter/article/remarks?id=' + $scope.id + '&' + page, function (rsp) {
            $scope.remarks = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.doSearch();
}]);
xxtApp.controller('StatCtrl', ['$scope', 'http2', function ($scope, http2) {
    http2.get('/rest/mp/matter/article/stat?id=' + $scope.id, function (rsp) {
        $scope.stat = rsp.data;
    });
}])
xxtApp.controller('ReadCtrl', ['$scope', 'http2', function ($scope, http2) {
    http2.get('/rest/mp/matter/article/read?id=' + $scope.id, function (rsp) {
        $scope.reads = rsp.data;
    });
}]);
