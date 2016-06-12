xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/mp/matter/article/edit', {
        templateUrl: '/views/default/mp/matter/article/edit.html?_=2',
        controller: 'editCtrl',
    }).when('/rest/mp/matter/article/read', {
        templateUrl: '/views/default/mp/matter/article/read.html?_=1',
        controller: 'readCtrl',
    }).when('/rest/mp/matter/article/remark', {
        templateUrl: '/views/default/mp/matter/article/remark.html?_=1',
        controller: 'remarkCtrl',
    }).when('/rest/mp/matter/article/stat', {
        templateUrl: '/views/default/mp/matter/article/stat.html?_=1',
        controller: 'statCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/matter/article/edit.html?_=1',
        controller: 'editCtrl'
    });
}]);
xxtApp.controller('articleCtrl', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.id = $location.search().id;
    $scope.subView = '';
    $scope.back = function() {
        history.back();
    };
    $scope.entryUrl = '';
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = rsp.data.parent_mpid && rsp.data.parent_mpid.length;
        http2.get('/rest/mp/matter/article/get?id=' + $scope.id, function(rsp) {
            $scope.editing = rsp.data;
            $scope.editing.attachments === undefined && ($scope.editing.attachments = []);
            $scope.entryUrl = 'http://' + location.host + '/rest/mi/matter?mpid=' + $scope.mpaccount.mpid + '&id=' + $scope.id + '&type=article';
            $scope.entryUrl += '&tpl=' + ($scope.editing.custom_body === 'N' ? 'std' : 'cus');
            if (!$scope.editing.creater)
                $scope.bodyEditable = false;
            else
                $scope.bodyEditable = true;
        });
    });
}]);
xxtApp.controller('editCtrl', ['$scope', '$uibModal', 'http2', 'templateShop', function($scope, $uibModal, http2, templateShop) {
    $scope.$parent.subView = 'edit';
    $scope.innerlinkTypes = [{
        value: 'article',
        title: '单图文',
        url: '/rest/mp/matter'
    }, {
        value: 'news',
        title: '多图文',
        url: '/rest/mp/matter'
    }, {
        value: 'channel',
        title: '频道',
        url: '/rest/mp/matter'
    }];
    var modifiedData = {};
    $scope.modified = false;
    window.onbeforeunload = function(e) {
        var message;
        if ($scope.modified) {
            message = '修改还没有保存，是否要离开当前页面？',
                e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.onBodyChange = function() {
        $scope.modified = true;
        modifiedData['body'] = encodeURIComponent($scope.editing['body']);
    };
    $scope.submit = function() {

        http2.post('/rest/mp/matter/article/update?id=' + $scope.editing.id, modifiedData, function() {
            modifiedData = {};
            $scope.modified = false;
        });
    };
    $scope.update = function(name) {
        $scope.modified = true;
        modifiedData[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                $scope.update('pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.$on('tinymce.multipleimage.open', function(event, callback) {
        var options = {
            callback: callback,
            multiple: true,
            setshowname: true
        };
        $scope.$broadcast('mediagallery.open', options);
    });
    $scope.delAttachment = function(index, att) {
        $scope.$root.progmsg = '删除文件';
        http2.get('/rest/mp/matter/article/attachmentDel?id=' + att.id, function success(rsp) {
            $scope.editing.attachments.splice(index, 1);
            $scope.$root.progmsg = null;
        });
    };
    $scope.downloadUrl = function(att) {
        return '/rest/mi/article/attachmentGet?mpid=' + $scope.editing.mpid + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
    };
    $scope.gotoCode = function() {
        if ($scope.editing.page_id != 0) {
            window.open('/rest/code?pid=' + $scope.editing.page_id);
        } else {
            http2.get('/rest/code/create', function(rsp) {
                var nv = {
                    'page_id': rsp.data.id
                };
                http2.post('/rest/mp/matter/article/update?id=' + $scope.editing.id, nv, function() {
                    $scope.editing.page_id = rsp.data.id;
                    //location.href = '/rest/code?pid=' + rsp.data.id;
                    window.open('/rest/code?pid=' + rsp.data.id);
                });
            });
        }
    };
    $scope.selectTemplate = function() {
        templateShop.choose('article').then(function(data) {
            http2.get('/rest/mp/matter/article/pageByTemplate?id=' + $scope.editing.id + '&template=' + data.id, function(rsp) {
                $scope.editing.page_id = rsp.data;
                location.href = '/rest/code?pid=' + rsp.data;
            });
        });
    };
    $scope.saveAsTemplate = function() {
        var matter, editing;
        editing = $scope.editing;
        matter = {
            id: editing.id,
            type: 'article',
            title: editing.title,
            pic: editing.pic,
            summary: editing.summary
        };
        templateShop.share($scope.mpaccount.mpid, matter).then(function() {
            $scope.$root.infomsg = '成功';
        });
    };
    $scope.embedMatter = function() {
        $scope.$broadcast('mattersgallery.open', function(matters, type) {
            var editor, dom, i, matter, mtype, fn;
            editor = tinymce.get('body1');
            dom = editor.dom;
            for (i = 0; i < matters.length; i++) {
                matter = matters[i];
                mtype = matter.type ? matter.type : type;
                fn = "openMatter($event," + matter.id + ",'" + mtype + "')";
                editor.insertContent(dom.createHTML('p', {
                    'class': 'matter-link'
                }, dom.createHTML('a', {
                    "ng-click": fn,
                }, dom.encode(matter.title))));
            }
        });
    };
    var insertVideo = function(url) {
        var editor, dom, html;
        if (url.length > 0) {
            editor = tinymce.get('body1');
            dom = editor.dom;
            html = dom.createHTML('p', {},
                dom.createHTML(
                    'video', {
                        style: 'width:100%',
                        controls: "controls",
                    },
                    dom.createHTML(
                        'source', {
                            src: url,
                            type: "video/mp4",
                        })
                )
            );
            editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
        }
    };
    $scope.embedVideo = function() {
        $uibModal.open({
            templateUrl: 'insertMedia.html',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.data = {
                    url: ''
                };
                $scope.cancel = function() {
                    $mi.dismiss()
                };
                $scope.ok = function() {
                    $mi.close($scope.data)
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            insertVideo(data.url);
        });
    };
    var insertAudio = function(url) {
        var editor, dom, html;
        if (url.length > 0) {
            editor = tinymce.get('body1');
            dom = editor.dom;
            html = dom.createHTML('p', {}, dom.createHTML('audio', {
                src: url,
                controls: "controls",
            }));
            editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
        }
    };
    $scope.embedAudio = function() {
        if ($scope.mpaccount._env.SAE) {
            $uibModal.open({
                templateUrl: 'insertMedia.html',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.data = {
                        url: ''
                    };
                    $scope.cancel = function() {
                        $mi.dismiss()
                    };
                    $scope.ok = function() {
                        $mi.close($scope.data)
                    };
                }],
                backdrop: 'static',
            }).result.then(function(data) {
                insertAudio(data.url);
            });
        } else {
            $scope.$broadcast('mediagallery.open', {
                mediaType: '音频',
                callback: insertAudio
            });
        }
    };
    $scope.upload2Mp = function() {
        var url;
        url = '/rest/mp/matter/article/upload2Mp?id=' + $scope.id;
        $scope.editing.media_id && $scope.editing.media_id.length && (url += '&mediaId=' + $scope.editing.media_id);
        http2.get(url, function(rsp) {
            $scope.editing.media_id = rsp.data.media_id;
            $scope.editing.upload_at = rsp.data.upload_at;
            $scope.$root.infomsg = '上传成功';
        });
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
        var aNewTags = [];
        angular.forEach(aSelected, function(selected) {
            var existing = false;
            angular.forEach($scope.editing.tags, function(tag) {
                if (selected.title === tag.title) {
                    existing = true;
                }
            });
            !existing && aNewTags.push(selected);
        });
        http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, aNewTags, function(rsp) {
            $scope.editing.tags = $scope.editing.tags.concat(aNewTags);
        });
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag) {
        var oNewTag = {
            title: newTag
        };
        http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, [oNewTag], function(rsp) {
            $scope.editing.tags.push(oNewTag);
        });
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed) {
        http2.post('/rest/mp/matter/article/removeTag?id=' + $scope.editing.id, [removed], function(rsp) {
            $scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    $scope.$on('tag2.xxt.combox.done', function(event, aSelected) {
        var aNewTags = [];
        angular.forEach(aSelected, function(selected) {
            var existing = false;
            angular.forEach($scope.editing.tags2, function(tag) {
                if (selected.title === tag.title) {
                    existing = true;
                }
            });
            !existing && aNewTags.push(selected);
        });
        http2.post('/rest/mp/matter/article/addTag2?id=' + $scope.id, aNewTags, function(rsp) {
            $scope.editing.tags2 = $scope.editing.tags2.concat(aNewTags);
        });
    });
    $scope.$on('tag2.xxt.combox.add', function(event, newTag) {
        var oNewTag = {
            title: newTag
        };
        http2.post('/rest/mp/matter/article/addTag2?id=' + $scope.id, [oNewTag], function(rsp) {
            $scope.editing.tags2.push(oNewTag);
        });
    });
    $scope.$on('tag2.xxt.combox.del', function(event, removed) {
        http2.post('/rest/mp/matter/article/removeTag2?id=' + $scope.editing.id, [removed], function(rsp) {
            $scope.editing.tags2.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    http2.get('/rest/mp/matter/tag?resType=article&subType=0', function(rsp) {
        $scope.tags = rsp.data;
    });
    http2.get('/rest/mp/matter/tag?resType=article&subType=1', function(rsp) {
        $scope.tags2 = rsp.data;
    });
    http2.get('/rest/mp/feature/get?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    var r = new Resumable({
        target: '/rest/mp/matter/article/upload?articleid=' + $scope.id,
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
        http2.post('/rest/mp/matter/article/attachmentAdd?id=' + $scope.id, posted, function success(rsp) {
            $scope.editing.attachments.push(rsp.data);
            $scope.$root.progmsg = null;
        });
    });
    $scope.$watch('editing.custom_body', function(nv) {
        if (!nv) return;
        $scope.entryUrl = $scope.entryUrl.replace(/tpl=[^&]*/, nv === 'Y' ? 'tpl=cus' : 'tpl=std');
    });
}]);
xxtApp.controller('remarkCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'remark';
    $scope.page = {
        current: 1,
        size: 30
    };
    $scope.delRemark = function(remark, index) {
        var ret = window.prompt('删除当前评论吗？请输入文章的标题');
        if (ret === $scope.editing.title) {
            http2.get('/rest/mp/matter/article/remarkDel?id=' + remark.id, function(rsp) {
                $scope.remarks.splice(index, 1);
            });
        }
    };
    $scope.cleanRemark = function() {
        var ret = window.prompt('删除当前评论吗？请输入文章的标题');
        if (ret === $scope.editing.title) {
            http2.get('/rest/mp/matter/article/remarkClean?articleid=' + $scope.id, function(rsp) {
                $scope.remarks = [];
            });
        }
    };
    $scope.doSearch = function() {
        var page = 'page=' + $scope.page.current + '&size=' + $scope.page.size;
        http2.get('/rest/mp/matter/article/remarkGet?id=' + $scope.id + '&' + page, function(rsp) {
            $scope.remarks = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.doSearch();
}]);
xxtApp.controller('statCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'stat';
}])
xxtApp.controller('readCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'read';
    http2.get('/rest/mp/matter/article/readGet?id=' + $scope.id, function(rsp) {
        $scope.reads = rsp.data;
    });
}]);