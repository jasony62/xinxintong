define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'noticebox', 'mattersgallery', 'mediagallery', 'noticebox', 'srvApp', 'cstApp', 'tmsThumbnail', '$timeout', function($scope, $uibModal, http2, noticebox, mattersgallery, mediagallery, noticebox, srvApp, cstApp, tmsThumbnail, $timeout) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.editing.title + '二维码.png"></a>')[0].click();
        };
        var tinymceEditor, modifiedData = {};

        $scope.modified = false;
        $scope.innerlinkTypes = cstApp.innerlink;
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
            if (!$scope.editing.pic && !$scope.editing.thumbnail) {
                tmsThumbnail.thumbnail($scope.editing);
            }
        };
        $scope.submit = function() {
            http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, modifiedData, function() {
                modifiedData = {};
                $scope.modified = false;
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/article/remove?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, function(rsp) {
                    if ($scope.editing.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.editing.siteid + "&id=" + $scope.editing.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.editing.siteid;
                    }
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                    srvApp.update('pic');
                }
            };
            mediagallery.open($scope.editing.siteid, options);
        };
        $scope.removePic = function() {
            $scope.editing.pic = '';
            srvApp.update('pic');
        };
        $scope.assignMission = function() {
            srvApp.assignMission().then(function(mission) {});
        };
        $scope.quitMission = function() {
            srvApp.quitMission().then(function() {});
        };
        $scope.choosePhase = function() {
            srvApp.choosePhase();
        };
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.editing.siteid, options);
        });
        $scope.embedMatter = function() {
            var options = {
                matterTypes: $scope.innerlinkTypes,
                singleMatter: true
            };
            if ($scope.editing.mission) {
                options.mission = $scope.editing.mission;
            }
            mattersgallery.open($scope.editing.siteid, function(matters, type) {
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
                        angular.forEach(matters, function(matter) {
                            fn = "openMatter($event,'" + matter.id + "','" + type + "')";
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
                        angular.forEach(matters, function(matter) {
                            fn = "openMatter($event,'" + matter.id + "','" + type + "')";
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
            }, options);
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
            http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, aNewTags, function(rsp) {
                $scope.editing.tags = $scope.editing.tags.concat(aNewTags);
            });
        });
        $scope.$on('tag.xxt.combox.add', function(event, newTag) {
            var oNewTag = {
                title: newTag
            };
            http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, [oNewTag], function(rsp) {
                $scope.editing.tags.push(oNewTag);
            });
        });
        $scope.$on('tag.xxt.combox.del', function(event, removed) {
            http2.post('/rest/pl/fe/matter/article/tag/remove?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, [removed], function(rsp) {
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
            http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, aNewTags, function(rsp) {
                $scope.editing.tags2 = $scope.editing.tags2.concat(aNewTags);
            });
        });
        $scope.$on('tag2.xxt.combox.add', function(event, newTag) {
            var oNewTag = {
                title: newTag
            };
            http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, [oNewTag], function(rsp) {
                $scope.editing.tags2.push(oNewTag);
            });
        });
        $scope.$on('tag2.xxt.combox.del', function(event, removed) {
            http2.post('/rest/pl/fe/matter/article/tag/remove2?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, [removed], function(rsp) {
                $scope.editing.tags2.splice($scope.editing.tags2.indexOf(removed), 1);
            });
        });
        $scope.delAttachment = function(index, att) {
            http2.get('/rest/pl/fe/matter/article/attachment/del?site=' + $scope.editing.siteid + '&id=' + att.id, function success(rsp) {
                $scope.editing.attachments.splice(index, 1);
            });
        };
        $scope.downloadUrl = function(att) {
            return '/rest/site/fe/matter/article/attachmentGet?site=' + $scope.editing.siteid + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
        };
        $scope.$watch('editing', function(editing) {
            if (!editing) return;
            http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.editing.siteid + '&resType=article&subType=0', function(rsp) {
                $scope.tags = rsp.data;
            });
            http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.editing.siteid + '&resType=article&subType=1', function(rsp) {
                $scope.tags2 = rsp.data;
            });
            if (tinymceEditor) {
                tinymceEditor.setContent(editing.body);
            }
            var r = new Resumable({
                target: '/rest/pl/fe/matter/article/attachment/upload?site=' + $scope.editing.siteid + '&articleid=' + $scope.editing.id,
                testChunks: false,
            });
            r.assignBrowse(document.getElementById('addAttachment'));
            r.on('fileAdded', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('开始上传文件');
                });
                r.upload();
            });
            r.on('progress', function(file, event) {
                $scope.$apply(function() {
                    noticebox.progress('正在上传文件：' + Math.floor(r.progress() * 100) + '%');
                });
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
                http2.post('/rest/pl/fe/matter/article/attachment/add?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, posted, function success(rsp) {
                    $scope.editing.attachments.push(rsp.data);
                });
            });
        });
        //更改缩略图
        $scope.$watch('editing.title', function(title, oldTitle) {
            //如果数据不为空，
            // 如果图片为空 ，且 标题第一个字发生变化 则更改缩略图
            //且上一个
            if ($scope.editing) {
                if (!$scope.editing.pic && title.slice(0, 1) != oldTitle.slice(0, 1)) {
                    $timeout(function() {
                        tmsThumbnail.thumbnail($scope.editing);
                    }, 3000);
                }
            }
        });
        $scope.$on('tinymce.instance.init', function(event, editor) {
            tinymceEditor = editor;
            if ($scope.editing) {
                editor.setContent($scope.editing.body);
            }
        });
        $scope.$on('tinymce.content.change', function(event, changed) {
            var content;
            content = tinymceEditor.getContent();
            if (content !== $scope.editing.body) {
                $scope.editing.body = content;
                modifiedData['body'] = encodeURIComponent(content);
                $scope.modified = true;
            }
        });
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.editing.siteid + '&type=article&id=' + $scope.editing.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        };
    }]);
});
