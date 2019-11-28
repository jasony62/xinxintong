define(['frame'], function (ngApp) {
    ngApp.provider.controller('ctrlBody', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvSite', 'mediagallery', 'noticebox', 'srvApp', 'cstApp', '$timeout', 'tmsThumbnail', function ($scope, $uibModal, http2, noticebox, srvSite, mediagallery, noticebox, srvApp, cstApp, $timeout, tmsThumbnail) {
        var tinymceEditor;
        var modifiedData = {};

        $scope.modified = false;
        $scope.innerlinkTypes = cstApp.innerlink;
        window.onbeforeunload = function (e) {
            var message;
            if ($scope.modified) {
                message = '修改还没有保存，是否要离开当前页面？',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
            if ($scope.editing) {
                if (!$scope.editing.pic && !$scope.editing.thumbnail) {
                    tmsThumbnail.thumbnail($scope.editing);
                }
            }
        };
        $scope.submit = function () {
            http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, modifiedData).then(function () {
                modifiedData = {};
                $scope.modified = false;
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function () {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/article/remove?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id).then(function (rsp) {
                    if ($scope.editing.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.editing.siteid + "&id=" + $scope.editing.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.editing.siteid;
                    }
                });
            }
        };
        $scope.$on('tinymce.multipleimage.open', function (event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.editing.siteid, options);
        });
        $scope.embedMatter = function () {
            var options = {
                matterTypes: $scope.innerlinkTypes,
                singleMatter: true
            };
            if ($scope.editing.mission) {
                options.mission = $scope.editing.mission;
            }
            srvSite.openGallery(options).then(function (result) {
                var editor = tinymce.get('body1'),
                    dom = editor.dom,
                    selection = editor.selection,
                    sibling, domMatter, fn, style;

                style = "cursor:pointer";
                if (selection && selection.getNode()) {
                    /*选中了页面上已有的元素*/
                    sibling = selection.getNode();
                    if (sibling !== editor.getBody()) {
                        while (sibling.parentNode !== editor.getBody()) {
                            sibling = sibling.parentNode;
                        }
                        angular.forEach(result.matters, function (matter) {
                            fn = "openMatter($event,'" + matter.id + "','" + result.type + "')";
                            domMatter = dom.create('p', {
                                'wrap': 'matter'
                            }, dom.createHTML('span', {
                                "ng-click": fn,
                                "style": style
                            }, dom.encode(matter.title)));
                            dom.insertAfter(domMatter, sibling);
                            selection.setCursorLocation(domMatter, 0);
                        });
                    } else {
                        /*没有选中页面上的元素*/
                        angular.forEach(result.matters, function (matter) {
                            fn = "openMatter($event,'" + matter.id + "','" + result.type + "')";
                            domMatter = dom.add(editor.getBody(), 'p', {
                                'wrap': 'matter'
                            }, dom.createHTML('span', {
                                "ng-click": fn,
                                "style": style
                            }, dom.encode(matter.title)));
                            selection.setCursorLocation(domMatter, 0);
                        });
                    }
                    editor.focus();
                }
            });
        };
        var insertLink = function (data) {
            var editor, dom, html;
            if (data.url.length > 0) {
                editor = tinymce.get('body1');
                dom = editor.dom;
                html = dom.createHTML('p', {},
                    dom.createHTML('a', {
                        style: 'display:block',
                        href: data.url,
                    }, dom.encode(data.text)));
                editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
            }
        }
        $scope.embedLink = function () {
            $uibModal.open({
                templateUrl: 'insertMedia.html',
                controller: ['$uibModalInstance', '$scope', function ($mi, $scope) {
                    $scope.embedType = 'link';
                    $scope.data = {
                        url: '',
                        text: ''
                    };
                    $scope.cancel = function () {
                        $mi.dismiss()
                    };
                    $scope.ok = function () {
                        $mi.close($scope.data)
                    };
                }],
                backdrop: 'static',
            }).result.then(function (data) {
                insertLink(data);
            });
        }
        var insertVideo = function (url) {
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
                            }
                        )
                    )
                );
                editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
            }
        };
        $scope.embedVideo = function () {
            $uibModal.open({
                templateUrl: 'insertMedia.html',
                controller: ['$uibModalInstance', '$scope', function ($mi, $scope) {
                    $scope.embedType = 'video';
                    $scope.data = {
                        url: ''
                    };
                    $scope.cancel = function () {
                        $mi.dismiss()
                    };
                    $scope.ok = function () {
                        $mi.close($scope.data)
                    };
                }],
                backdrop: 'static',
            }).result.then(function (data) {
                insertVideo(data.url);
            });
        };
        var insertAudio = function (url) {
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
        $scope.embedAudio = function () {
            $uibModal.open({
                templateUrl: 'insertMedia.html',
                controller: ['$uibModalInstance', '$scope', function ($mi, $scope) {
                    $scope.embedType = 'audio';
                    $scope.data = {
                        url: ''
                    };
                    $scope.cancel = function () {
                        $mi.dismiss()
                    };
                    $scope.ok = function () {
                        $mi.close($scope.data)
                    };
                }],
                backdrop: 'static',
            }).result.then(function (data) {
                insertAudio(data.url);
            });
        };
        $scope.delAttachment = function (index, att) {
            http2.get('/rest/pl/fe/matter/article/attachment/del?site=' + $scope.editing.siteid + '&id=' + att.id).then(function (rsp) {
                $scope.editing.attachments.splice(index, 1);
            });
        };
        $scope.downloadUrl = function (att) {
            return '/rest/site/fe/matter/article/attachmentGet?site=' + $scope.editing.siteid + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
        };

        $scope.$watch('editing.body_md', function (content) {
            modifiedData.body_md = content;
            $scope.modified = true;
        });
        $scope.$watch('editing', function (editing) {
            var filemaxsize = 0
            if (!editing) return;
            if (editing.is_markdown !== 'Y')
                if (tinymceEditor)
                    tinymceEditor.setContent(editing.body);
            if (window.sessionStorage) {
                var oSessionCached, filetypes;
                if (window.sessionStorage.getItem('xxt.pl.protect.system')) {
                    oSessionCached = window.sessionStorage.getItem('xxt.pl.protect.system');
                    oSessionCached = JSON.parse(oSessionCached);
                    filetypes = oSessionCached.fileContentTypeWhite;
                    filemaxsize = oSessionCached.fileMaxSize;
                }
            }
            var r = new Resumable({
                target: '/rest/pl/fe/matter/article/attachment/upload?site=' + $scope.editing.siteid + '&articleid=' + $scope.editing.id,
                testChunks: false,
            });
            r.assignBrowse(document.getElementById('addAttachment'));
            r.on('fileAdded', function (oFile, event) {
                var f = oFile.file;
                if (filetypes) {
                    if (!(f.name.lastIndexOf("."))) {
                        noticebox.error("文件数据格式错误:只能上传" + filetypes + "格式的文件");
                        return false;
                    }
                    var seat = f.name.lastIndexOf(".") + 1,
                        extendsion = f.name.substring(seat).toLowerCase();
                    if (filetypes.indexOf(extendsion) === -1) {
                        noticebox.error("文件数据格式错误:只能上传" + filetypes + "格式的文件");
                        return false;
                    }
                }
                if (filemaxsize !== 0 && filemaxsize * 1024 * 1024 <= f.size) {
                    noticebox.error("文件上传失败，大小不能超过" + filemaxsize + "M");
                    return false;
                }
                $scope.$apply(function () {
                    noticebox.progress('开始上传文件');
                });
                r.upload();
            });
            r.on('progress', function (file, event) {
                $scope.$apply(function () {
                    noticebox.progress('正在上传文件：' + Math.floor(r.progress() * 100) + '%');
                    if (Math.floor(r.progress() * 100) === 100) {
                        noticebox.close();
                    }
                });
            });
            /*r.on('error', function (message, file) {
                noticebox.error('上传发生错误，请稍后再试!');
               
            });*/
            r.on('complete', function () {
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

                http2.post('/rest/pl/fe/matter/article/attachment/add?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, posted).then(function (rsp) {
                    $scope.editing.attachments.push(rsp.data);
                });
            });
        });
        $scope.$on('tinymce.instance.init', function (event, editor) {
            tinymceEditor = editor;
            if ($scope.editing) {
                if ($scope.editing.is_markdown !== 'Y') {
                    editor.setContent($scope.editing.body);
                }
            }
        });
        $scope.$on('tinymce.content.change', function (event, changed) {
            var content;
            var oArticle = $scope.editing;
            if (oArticle.is_markdown !== 'Y') {
                content = tinymceEditor.getContent();
                if (content !== oArticle.body) {
                    oArticle.body = content;
                    modifiedData.body = encodeURIComponent(content);
                    $scope.modified = true;
                }
            }
        });
    }]);
});