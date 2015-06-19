angular.module('matters.xxt', ['ui.bootstrap'])
    .constant('matterTypes', [
    { value: 'text', title: '文本', url: '/rest/mp/matter' },
    { value: 'article', title: '单图文', url: '/rest/mp/matter' },
    { value: 'news', title: '多图文', url: '/rest/mp/matter' },
    { value: 'channel', title: '频道', url: '/rest/mp/matter' },
    { value: 'link', title: '链接', url: '/rest/mp/matter' },
    { value: 'addressbook', title: '通讯录', url: '/rest/mp/matter' },
    { value: 'enroll', title: '登记活动', url: '/rest/mp/matter' },
    { value: 'enrollsignin', title: '登记活动签到', url: '/rest/mp/matter' },
    { value: 'lottery', title: '抽奖活动', url: '/rest/mp/matter' },
    { value: 'wall', title: '讨论组', url: '/rest/mp/matter' },
    { value: 'joinwall', title: '加入讨论组', url: '/rest/mp/matter' },
    { value: 'contribute', title: '投稿活动', url: '/rest/mp/app' },
    { value: 'inner', title: '内置回复', url: '/rest/mp/matter' },
    { value: 'relay', title: '转发消息', url: '/rest/mp/matter' },
]).service('userSetAsParam', [function () {
    this.convert = function (userSet) {
        if (userSet.userScope === '') return [];
        var params = [], i, dept, tagIds = [], tagNames = [];
        switch (userSet.userScope) {
            case 'a':
                var newUs = {
                    identity: -1,
                    idsrc: 'G',
                    label: '所有关注用户'
                };
                params.push(newUs);
                break;
            case 'g':
                var group;
                for (i = 0; i < userSet.fansGroup.length; i++) {
                    group = userSet.fansGroup[i];
                    var newUs = {
                        identity: group.id,
                        idsrc: 'G',
                        label: group.name
                    };
                    params.push(newUs);
                }
                break;
            default:
                if (userSet.tags && userSet.tags.length) {
                    for (i = 0; i < userSet.tags.length; i++) {
                        tagIds.push(userSet.tags[i].id);
                        tagNames.push(userSet.tags[i].name);
                    }
                }
                if (userSet.depts && userSet.depts.length) {
                    for (i in userSet.depts) {
                        dept = userSet.depts[i];
                        var newUs = {
                            identity: dept.id + (tagIds.length > 0 ? ',' + tagIds.join(',') : ''),
                            idsrc: tagIds.length > 0 ? 'DT' : 'D',
                            label: dept.name + (tagNames.length > 0 ? ',' + tagNames.join(',') : '')
                        };
                        params.push(newUs);
                    }
                } else if (tagIds.length) {
                    var newUs = {
                        identity: tagIds.join(','),
                        idsrc: 'T',
                        label: tagNames.join(',')
                    };
                    params.push(newUs);
                } else if (userSet.members && userSet.members.length) {
                    var newUs, member;
                    for (var i in userSet.members) {
                        member = userSet.members[i];
                        newUs = {
                            identity: member.mid || member.authed_identity,
                            idsrc: 'M',
                            label: member.name || member.nickname || member.email || member.mobile
                        };
                        params.push(newUs);
                    }
                }
        }
        return params;
    };
}]).filter('typetitle', ['matterTypes', function (matterTypes) {
    return function (type) {
        for (var i in matterTypes) {
            if (type && type.toLowerCase() === matterTypes[i].value)
                return matterTypes[i].title;
        }
        return '';
    }
}]).directive('tinymce', function ($timeout) {
    return {
        restrict: 'EA',
        scope: { id: '@', height: '=', content: '=', contenteditable: '=', update: '&', change: '&' },
        replace: true,
        template: '<textarea></textarea>',
        link: function (scope, elem, attrs) {
            setTimeout(function () {
                tinymce.init({
                    selector: '#' + scope.id,
                    language: 'zh_CN',
                    menubar: false,
                    statusbar: false,
                    plugins: ['textcolor code table paste fullscreen visualblocks'],
                    toolbar: 'fontsizeselect styleselect forecolor backcolor bullist numlist outdent indent table multipleimage fullscreen visualblocks code',
                    content_css: '/static/css/bootstrap.min.css,/static/css/tinymce.css?v=3',
                    forced_root_block: 'p',
                    height: scope.height ? scope.height : 300,
                    valid_elements: "*[*]",
                    relative_urls: false,
                    setup: function (editor) {
                        editor.on('click', function (e) {
                            var wrap;
                            wrap = e.target;
                            if (wrap.tagName !== 'HTML') {
                                if (wrap !== editor.getBody()) {
                                    while (wrap.parentNode !== editor.getBody()) {
                                        if (wrap.parentNode === null) break;
                                        wrap = wrap.parentNode;
                                    }
                                }
                                scope.$emit('tinymce.wrap.select', wrap);
                            } else {
                                scope.$emit('tinymce.wrap.select', editor.getBody());
                            }
                        });
                        editor.on('keydown', function (evt) {
                            if (evt.keyCode == 13) {
                                /**
                                 * 检查组件元素，如果是，在结尾回车时不进行元素的复制，而是添加空行
                                 */
                                var dom, wrap, selection;
                                dom = editor.dom;
                                selection = editor.selection;
                                wrap = selection.getNode();
                                while (wrap.parentNode !== editor.getBody()) {
                                    wrap = wrap.parentNode;
                                }
                                if (wrap.hasAttribute('wrap') && wrap.getAttribute('wrap') !== 'text') {
                                    evt.preventDefault();
                                    var newWrap = dom.create('div', { wrap: 'text', class: 'form-group' }, '&nbsp;');
                                    dom.insertAfter(newWrap, wrap);
                                    selection.setCursorLocation(newWrap, 0);
                                    editor.focus();
                                }
                            }
                        });
                        editor.on('change', function (e) {
                            var content, phase;
                            content = tinymce.get(scope.id).getContent();
                            if (scope.content !== content) {
                                var phase = scope.$root.$$phase;
                                if (phase === '$digest' || phase === '$apply') {
                                    scope.content = content;
                                } else {
                                    scope.$apply(function () {
                                        scope.content = content;
                                    });
                                }
                                $timeout(function () {
                                    scope.change && scope.change();
                                });
                            }
                        });
                        editor.on('blur', function (e) {
                            var content = tinymce.get(scope.id).getContent();
                            if (scope.content !== content) {
                                var phase = scope.$root.$$phase;
                                if (phase === '$digest' || phase === '$apply') {
                                    scope.content = content;
                                } else {
                                    scope.$apply(function () {
                                        scope.content = content;
                                    });
                                }
                                $timeout(function () {
                                    scope.update && scope.update();
                                });
                            }
                        });
                        editor.on('BeforeSetContent', function (e) {
                            if (e.content && e.content.length) {
                                var c = e.content;
                                c = c.replace(/\n|\r/g, '').replace(/\s*/, ''); // trim
                                if (/^<table.*<\/table>$/i.test(c)) {
                                    e.content = '<p>&nbsp;</p><div class="tablewrap">' + c + '</div><p>&nbsp;</p>';
                                } else if (/^<a.*<\/a>/i.test(c) && /link2email/.test(c) === false) {
                                    var $a = $(c);
                                    if ($a.attr('target') == '_email') {
                                        var href = $a.attr('href'), code = $a.attr('code'), html = $a.html();
                                        href += (href.indexOf('?') == -1 ? '?' : '&');
                                        href += 'code=' + code;
                                        href += '&text=' + html;
                                        e.content = '<a class="link2email btn" href="' + href + '">发送【' + html + '】的链接到绑定邮箱</a>';
                                    }
                                }
                            }
                        });
                        editor.on('ExecCommand', function (e) {
                            switch (e.command) {
                                case 'mceTableDelete':
                                    var c = this.getContent(), patt = /<div class="tablewrap">&nbsp;<\/div>/;
                                    if (patt.test(c)) {
                                        c = c.replace(patt, '');
                                        this.setContent(c);
                                    }
                                    break;
                            }
                        });
                        editor.addButton('multipleimage', {
                            tooltip: '插入图片',
                            icon: 'image',
                            onclick: function () {
                                var selectedNode, selectedId, tmpId = false;
                                selectedNode = editor.selection.getNode();
                                selectedId = editor.dom.getAttrib(selectedNode, 'id');
                                if (!selectedId) {
                                    tmpId = true;
                                    selectedId = '__mcenew' + (new Date).getTime();
                                    editor.dom.setAttrib(selectedNode, 'id', selectedId);
                                }
                                scope.$emit('tinymce.multipleimage.open', function (urls, isShowName) {
                                    var i, t, url, data, dom, pElm;
                                    t = (new Date()).getTime();
                                    dom = editor.dom;
                                    for (i in urls) {
                                        url = urls[i] + '?_=' + t,
                                        data = { src: url, style: 'max-width:100%' },
                                        pElm = dom.add(selectedId, 'p');
                                        dom.add(pElm, 'img', data);
                                        if (isShowName === 'Y') {
                                            var picname = decodeURI(urls[i]).split('/').pop();
                                            picname = picname.split('.').shift();
                                            dom.add(pElm, 'span', { style: 'display:block' }, picname);
                                        }
                                    }
                                    if (tmpId) {
                                        selectedNode = dom.get(selectedId);
                                        dom.setAttrib(selectedNode, 'id', null);
                                    }
                                    editor.save();
                                });
                            }
                        });
                    },
                    init_instance_callback: function () {
                        scope.initialized = true;
                        if (scope.content !== undefined)
                            tinymce.get(scope.id).setContent(scope.content);
                        if (scope.contenteditable !== undefined)
                            $(tinymce.activeEditor.getBody()).attr('contenteditable', scope.contenteditable);
                        scope.$emit('tinymce.instance.init');
                    }
                });
            }, 1);
            scope.$on('$destroy', function () {
                var tinyInstance;
                if (tinyInstance = tinymce.get(scope.id)) {
                    tinyInstance.remove();
                    tinyInstance = null;
                }
            });
        }
    }
}).controller('MattersGalleryModalInstCtrl', ['$scope', '$http', '$modalInstance', 'matterTypes', 'singleMatter', 'hasParent', function ($scope, $http, $modalInstance, matterTypes, singleMatter, hasParent) {
    $scope.matterTypes = matterTypes;
    $scope.singleMatter = singleMatter;
    $scope.hasParent = hasParent;
    $scope.p = {};
    if ($scope.matterTypes && $scope.matterTypes.length)
        $scope.p.matterType = $scope.matterTypes[0];

    var fields = ['id', 'title'];
    $scope.page = { current: 1, size: 10 };
    $scope.aChecked = [];
    $scope.doCheck = function (matter) {
        if ($scope.singleMatter) {
            $scope.aChecked = [matter];
        } else {
            var i = $scope.aChecked.indexOf(matter);
            if (i === -1)
                $scope.aChecked.push(matter);
            else
                $scope.aChecked.splice(i, 1);
        }
    };
    $scope.doSearch = function () {
        if (!$scope.p.matterType) return;
        var url = $scope.p.matterType.url;
        url += '/' + $scope.p.matterType.value;
        url += '/get?page=' + $scope.page.current + '&size=' + $scope.page.size + '&fields=' + fields;
        if ($scope.p.fromParent && $scope.p.fromParent == 1)
            url += '&src=p';
        $http.get(url, { headers: { 'ACCEPT': 'application/json' } }).
            success(function (rsp) {
            if ($scope.p.matterType.value === 'article' || $scope.p.matterType.value === 'contribute') {
                $scope.matters = rsp.data[0];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
            } else {
                $scope.matters = rsp.data;
                $scope.page.total = $scope.matters.length;
            }
        });
    };
    $scope.ok = function () {
        $modalInstance.close([$scope.aChecked, $scope.p.matterType ? $scope.p.matterType.value : 'article']);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    $scope.$watch('p.matterType', function (nv) {
        $scope.doSearch();
    });
}]).controller('MattersController', ['$scope', '$http', '$modal', function ($scope, $http, $modal) {
    var open = function () {
        $modal.open({
            templateUrl: 'modalMattersGalllery.html',
            controller: 'MattersGalleryModalInstCtrl',
            size: 'lg',
            backdrop: 'static',
            windowClass: 'auto-height mattersgallery',
            resolve: {
                singleMatter: function () {
                    return $scope.singleMatter ? $scope.singleMatter : false;
                },
                hasParent: function () {
                    return $scope.hasParent ? $scope.hasParent : false;
                },
                matterTypes: function () {
                    return $scope.matterTypes;
                }
            }
        }).result.then(function (result) {
            if ($scope.callback) {
                $scope.callback(result[0], result[1]);
            }
            $scope.$emit('mattersgallery.done', result[0]);
        });
    };
    $scope.$on('mattersgallery.open', function (event, callback) {
        $scope.callback = callback;
        open();
    });
}]).directive('mattersgallery', function () {
    return {
        restrict: 'EA',
        scope: { singleMatter: '@', hasParent: '@', matterTypes: '=' },
        controller: 'MattersController',
        templateUrl: '/static/template/mattersgallery.html?v=3',
    }
}).controller('PicGalleryModalInstCtrl', ['$scope', '$modalInstance', 'url', 'setshowname', function ($scope, $modalInstance, url, setshowname) {
    $scope.url = url;
    $scope.setshowname = setshowname;
    $scope.setting = { isShowName: 'N' };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    $scope.$watch('setting.isShowName', function (nv) {
        $modalInstance.isShowName = nv;
    });
}]).controller('PicController', ['$scope', '$http', '$modal', function ($scope, $http, $modal) {
    var modalInstance,
        open = function (setshowname) {
            modalInstance = $modal.open({
                templateUrl: 'modalPicGalllery.html',
                controller: 'PicGalleryModalInstCtrl',
                backdrop: 'static',
                size: 'lg',
                windowClass: 'auto-height picgallery',
                resolve: {
                    url: function () {
                        return $scope.picGalleryUrl;
                    },
                    setshowname: function () {
                        return setshowname;
                    }
                }
            });
        };
    $scope.$on('picgallery.open', function (event, callback, multiple, setshowname) {
        var kcfCallBack = function (url) {
            window.KCFinder = null;
            callback && callback(url, modalInstance.isShowName);
            modalInstance.close();
        };
        if (multiple)
            window.KCFinder = { callBackMultiple: kcfCallBack };
        else
            window.KCFinder = { callBack: kcfCallBack };
        open(setshowname);
    });
}]).directive('picgallery', function () {
    return {
        restrict: 'EA',
        scope: { picGalleryUrl: '@url' },
        controller: 'PicController',
        templateUrl: '/static/template/picgallery.html?_=1',
    }
}).controller('AccessControllerUserPickerController', ['$scope', '$modalInstance', 'userSetAsParam', function ($scope, $mi, userSetAsParam) {
    $scope.userConfig = { userScope: ['M'] };
    $scope.userSet = {};
    $scope.cancel = function () {
        $mi.dismiss();
    };
    $scope.ok = function () {
        var data = {};
        data.userScope = $scope.userSet.userScope;
        data.userSet = userSetAsParam.convert($scope.userSet);
        $mi.close(data);
    };
}]).controller('AccessControlController', ['$rootScope', '$scope', 'http2', '$timeout', '$modal', function ($rootScope, $scope, http2, $timeout, $modal) {
    var objAuthapis = function () {
        $scope.objAuthapis = angular.copy($scope.authapis);
        var aAuthapis = $scope.obj[$scope.propApis] ? $scope.obj[$scope.propApis].trim() : '';
        aAuthapis = aAuthapis.length === 0 ? [] : aAuthapis.split(',');
        for (var i in $scope.objAuthapis) {
            $scope.objAuthapis[i].checked = aAuthapis.indexOf($scope.objAuthapis[i].authid) !== -1 ? 'Y' : 'N';
        }
    };
    $scope.setAccessControl = function () {
        $scope.updateAccessControl();
        if ($scope.authapis.length === 1) {
            $scope.obj[$scope.propApis] = $scope.obj[$scope.propAccess] === 'Y' ? $scope.authapis[0].authid : '';
            $scope.objAuthapis[0].checked = $scope.obj[$scope.propAccess] === 'Y' ? 'Y' : 'N';
            $scope.updateAuthapis();
        }
    };
    $scope.setAuthapi = function (api) {
        var eapis, p = {};
        eapis = $scope.obj[$scope.propApis] ? $scope.obj[$scope.propApis].trim() : '';
        eapis = eapis.length === 0 ? [] : eapis.split(',');
        api.checked === 'Y' ? eapis.push(api.authid) : eapis.splice(eapis.indexOf(api.authid), 1);
        p.authapis = eapis.join();
        $scope.obj[$scope.propApis] = p.authapis;
        $scope.updateAuthapis();
        if (eapis.length === 0) {
            if ($scope.obj[$scope.propAccess] !== 'N') {
                $scope.obj[$scope.propAccess] = 'N';
                $scope.updateAccessControl();
            }
        } else {
            if ($scope.obj[$scope.propAccess] !== 'Y') {
                $scope.obj[$scope.propAccess] = 'Y';
                $scope.updateAccessControl();
            }
        }
    };
    $scope.addAcl = function () {
        var newAcl = { identity: '', idsrc: '' };
        $scope.obj[$scope.propAcl].push(newAcl);
        $timeout(function () { $('ul.acls li:last-child input').focus(); }, 10);
    };
    $scope.openAclSelector = function () {
        $modal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'AccessControllerUserPickerController',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (data) {
            var i, newAcl, addAcl;
            addAcl = function (rsp) {
                $scope.obj[$scope.propAcl].push(rsp.data);
            };
            for (i in data.userSet) {
                newAcl = data.userSet[i];
                http2.post($scope.changeAclUrl, newAcl, addAcl);
            }
        });
    };
    $scope.clickAcl = function (acl, state, event) {
        if (acl.idsrc.length === 0) {
            state.editing = true;
            var i = $scope.obj[$scope.propAcl].indexOf(acl) + 1;
            $timeout(function () { $('ul.acls li:nth-child(' + i + ') input').focus(); }, 10);
        }
    };
    $scope.changeAcl = function (newAcl, state) {
        http2.post($scope.changeAclUrl, newAcl, function (rsp) {
            if (newAcl.id === undefined)
                newAcl.id = rsp.data.id;
            if (newAcl.idsrc === '') newAcl.label = newAcl.identity;
            state.editing = false;
        });
    };
    $scope.removeAcl = function (acl, event) {
        event.preventDefault();
        event.stopPropagation();
        var i = $scope.obj[$scope.propAcl].indexOf(acl);
        if (acl.id === undefined)
            $scope.obj[$scope.propAcl].splice(i, 1);
        else {
            http2.get($scope.removeAclUrl + '?acl=' + acl.id, function (rsp) {
                $scope.obj[$scope.propAcl].splice(i, 1);
            });
        }
    };
    $scope.$watch('obj', function (obj) {
        if (obj && $scope.authapis) objAuthapis();
    });
    http2.get('/rest/mp/authapi/get?valid=Y', function (rsp) {
        $scope.authapis = rsp.data;
        if ($scope.obj) objAuthapis();
    });
}]).directive('accesscontrol', function () {
    return {
        restrict: 'EA',
        scope: { title: '@', label: '@', mpid: '@', obj: '=', propAcl: '@', labelOfList: '@', propAccess: '@', propApis: '@', changeAclUrl: '@', removeAclUrl: '@', updateAccessControl: '&', updateAuthapis: '&', labelSpan: '@', controlSpan: '@', disabled: '@', hideAccessControl: '@' },
        controller: 'AccessControlController',
        templateUrl: '/static/template/accesscontrol.html?_=5',
    }
}).directive('userpopover', ['http2', function (http2) {
    return {
        restrict: 'A',
        scope: { xxtFid: '@' },
        link: function (scope, elem, attrs) {
            $(elem).on('mouseenter', function (event) {
                if (!$(elem).attr('loaded')) {
                    http2.get('/rest/mp/user/fans/fan?fid=' + scope.xxtFid, function (rsp) {
                        var member, tags = [], depts = [], detail = '';
                        if (rsp.data.members) {
                            member = rsp.data.members[0];
                            if (member.depts && member.depts.length) {
                                for (var i in member.depts)
                                    depts.push(member.depts[i].name);
                                depts = depts.join(',');
                            }
                            if (member.tags && member.tags.length) {
                                for (var i in member.tags)
                                    tags.push(member.tags[i].name);
                                tags = tags.join(',');
                            }
                            if (depts.length) detail += depts;
                            if (detail.length) detail += ','
                            if (tags.length) detail += tags;
                            if (detail.length) {
                                $popover = $(elem);
                                $popover.attr('loaded', true).popover({
                                    html: true,
                                    title: '<span>' + rsp.data.nickname + '</span><button class="close" onclick="$popover.popover(\'hide\')"><span>&times;</span></button>',
                                    content: detail,
                                    trigger: 'hover'
                                }).popover('show');
                            }
                        }
                    });
                }
            });
        }
    };
}]).controller('SendmeController', ['$scope', 'http2', function ($scope, http2) {
    $scope.qrcodeShown = false;
    $scope.qrcode = function (matter, event) {
        if (!$scope.qrcodeShown) {
            var url = '/rest/mp/call/qrcode/createOneOff';
            url += '?matter_type=' + matter.type;
            url += '&matter_id=' + matter.id;
            if (matter.mpid !== undefined) url += '&mpid=' + matter.mpid;
            http2.get(url, function (rsp) {
                $popover = $(event.target);
                $popover.popover({
                    html: true,
                    title: '<span>扫描发送到手机</span><button class="close" onclick="$popover.popover(\'destroy\')"><span>&times;</span></button>',
                    content: "<div><img src='" + rsp.data.pic + "'></div>"
                });
                $popover.on('hidden.bs.popover', function () {
                    $popover.popover('destroy');
                    $scope.qrcodeShown = false;
                })
                $popover.popover('show');
                $scope.qrcodeShown = true;
            });
        }
    };
}]).controller('UserPickerController', ['http2', '$scope', function (http2, $scope) {
    var getPickedAuthapi = function () {
        var authid = $scope.userSet.userScope.split('_').pop();
        for (var i in $scope.authapis) {
            if (authid === $scope.authapis[i].authid)
                return $scope.authapis[i];
        }
    };
    $scope.showPickSingleMember = false;
    $scope.isPickSingleMember = 'N';
    $scope.isPickMember = function () {
        return /authid_\d+/.test($scope.userSet.userScope);
    };
    $scope.canGroup = function () {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('G') !== -1;
    };
    $scope.canMember = function () {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('M') !== -1;
    };
    $scope.pickMp = function (mp) {
        !$scope.userSet.childmps && ($scope.userSet.childmps = []);
        if (mp.checked === 'Y')
            $scope.userSet.childmps.push(mp);
        else
            $scope.userSet.childmps.splice($scope.userSet.childmps.indexOf(mp), 1);
    };
    $scope.pickGroup = function (g) {
        !$scope.userSet.fansGroup && ($scope.userSet.fansGroup = []);
        if (g.checked === 'Y')
            $scope.userSet.fansGroup.push(g);
        else
            $scope.userSet.fansGroup.splice($scope.userSet.fansGroup.indexOf(g), 1);
    };
    $scope.$watch('userSet.userScope', function (nv) {
        if (nv && nv.length) {
            if (nv === 'mp') {
                http2.get('/rest/mp/mpaccount/childmps', function (rsp) {
                    $scope.childmps = rsp.data;
                });
            } else if (nv === 'g' && $scope.groups === undefined) {
                http2.get('/rest/mp/user/fans/group', function (rsp) {
                    $scope.groups = rsp.data;
                });
            } else if (/authid_\d+/.test(nv)) {
                $scope.authapi = getPickedAuthapi();
                http2.get($scope.authapi.url + '/memberSelector?authid=' + $scope.authapi.authid, function (rsp) {
                    $.getScript(rsp.data.js, function () {
                        $scope.memberViewUrl = rsp.data.view;
                        $scope.$apply('memberViewUrl');
                    });
                });
            }
        }
    });
    $scope.$on('init.member.selector', function (event, config) {
        if (config && config.showPickSingleMember !== undefined)
            $scope.showPickSingleMember = config.showPickSingleMember;
    });
    $scope.$on('add.dept.member.selector', function (event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.push(dept);
    });
    $scope.$on('remove.dept.member.selector', function (event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.splice($scope.userSet.depts.indexOf(dept), 1);
    });
    $scope.$on('add.tag.member.selector', function (event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.push(tag);
    });
    $scope.$on('remove.tag.member.selector', function (event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.splice($scope.userSet.tags.indexOf(tag), 1);
    });
    $scope.$on('add.member.member.selector', function (event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.push(member);
    });
    $scope.$on('remove.member.member.selector', function (event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.splice($scope.userSet.members.indexOf(member), 1);
    });
    http2.get('/rest/member/authapis', function (rsp) {
        $scope.authapis = rsp.data;
    });
}]).directive('userpicker', ['http2', function (http2) {
    return {
        restrict: 'EA',
        scope: { userSet: '=', userConfig: '=' },
        controller: 'UserPickerController',
        templateUrl: function () {
            return '/rest/mp/user/picker';
        },
    };
}]).controller('PushMatterController', ['http2', '$scope', '$modalInstance', 'userSetAsParam', function (http2, $scope, $modalInstance, userSetAsParam) {
    $scope.userConfig = { userScope: ['G'] };
    $scope.userSet = {};
    $scope.cancel = function () {
        $modalInstance.dismiss();
    };
    $scope.ok = function () {
        var targetUser, data;
        targetUser = /authid_\d+/.test($scope.userSet.userScope) ? 'M' : 'F';
        data = {
            targetUser: targetUser
        };
        if (targetUser === 'F') {
            if ($scope.userSet.userScope === 'mp')
                data.mps = $scope.userSet.childmps;
            else if ($scope.userSet.userScope === 'a')
                data.allUsers = 'Y';
            else if ($scope.userScope == 'g')
                data.gs = $scope.userSet.fansGroup;
        } else
            data.userSet = userSetAsParam.convert($scope.userSet);

        $modalInstance.close(data);
    };
    http2.get('/rest/mp/mpaccount/apis', function (rsp) {
        if (rsp.data.mpsrc === 'qy' || (rsp.data.mpsrc === 'yx' && rsp.data.yx_p2p))
            $scope.userConfig.userScope.push('M');
    });
}]).directive('pushmatter', function () {
    return {
        restrict: 'E',
        scope: { matterId: '@', matterType: '@' },
        controller: ['$rootScope', '$scope', '$modal', 'http2', function ($rootScope, $scope, $modal, http2) {
            $scope.open = function () {
                $modal.open({
                    templateUrl: '/static/template/pushmatter.html?_=3',
                    controller: 'PushMatterController',
                    backdrop: 'static',
                    size: 'lg',
                    windowClass: 'auto-height'
                }).result.then(function (data) {
                    data.id = $scope.matterId;
                    data.type = $scope.matterType;
                    if (data.mps !== undefined) {
                        var i = 0, mps = [];
                        for (i; i < data.mps.length; i++) {
                            mps.push(data.mps[i].mpid);
                        }
                        data.mps = mps;
                        http2.post('/rest/mp/send/mass2mps', data, function (rsp) {
                            $rootScope.infomsg = '发送完成';
                        });
                    } else {
                        http2.post('/rest/mp/send/mass', data, function (rsp) {
                            $rootScope.infomsg = '发送完成';
                        });
                    }
                });
            };
        }],
        replace: true,
        transclude: true,
        template: "<button ng-click='open()' ng-transclude></button>",
    };
}).directive('matterShop', ['$q', 'http2', function ($q, http2) {
    var Shop = function (type, mpid) {
        this.type = type;
        this.mpid = mpid === undefined ? '' : mpid;
        this.baseUrl = '/rest/shop/';
    };
    Shop.prototype.get = function (id) {
        var url, deferred, promise;
        deferred = $q.defer();
        promise = deferred.promise;
        url = this.baseUrl + 'shelf/get';
        url += '?mattertype=' + this.type;
        url += '&matterid=' + id;
        http2.get(url, function (rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Shop.prototype.listGet = function () {
        var url, _this = this;
        url = this.baseUrl + 'shelf/list';
        url += '?mattertype=' + this.type;
        http2.get(url, function (rsp) {
            _this.items = rsp.data;
        });
    };
    Shop.prototype.put = function (matter) {
        var url, deferred, promise;
        deferred = $q.defer();
        promise = deferred.promise;
        url = this.baseUrl + 'shelf/put';
        url += '?mpid=' + this.mpid;
        http2.post(url, matter, function (rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Shop.prototype.update = function (id, nv) {
        var url = this.baseUrl + 'shelf/update';
        url += '?id=' + id;
        http2.post(url, nv);
    };
    return {
        restrict: 'A',
        controller: ['$rootScope', '$scope', '$modal', 'http2', function ($rootScope, $scope, $modal, http2) {
            var open = function (matter, mpid, isOne) {
                $modal.open({
                    templateUrl: '/static/template/mattershop.html?_=1',
                    controller: function ($scope, $modalInstance) {
                        $scope.cancel = function () {
                            $modalInstance.dismiss();
                        };
                        $scope.put = function () {
                            $scope.Shop.put($scope.editing).then(function (data) {
                                $scope.isNew = false;
                                $scope.editing = data;
                                $modalInstance.dismiss();
                            });
                        };
                        $scope.update = function (prop) {
                            if ($scope.isNew) return;
                            var nv = {};
                            nv[prop] = $scope.editing[prop];
                            $scope.Shop.update($scope.editing.id, nv);
                        };
                        $scope.copy = function (item) {
                            $modalInstance.close(item);
                        };
                        $scope.isOne = isOne;
                        $scope.Shop = new Shop(matter.type, mpid);
                        if (isOne) {
                            $scope.Shop.get(matter.id).then(function (data) {
                                if (data) {
                                    $scope.editing = data;
                                } else {
                                    $scope.isNew = true;
                                    $scope.editing = { matter_id: matter.id, matter_type: matter.type, title: matter.title, pic: matter.pic, summary: matter.summary };
                                }
                            });
                        } else {
                            $scope.Shop.listGet();
                        }
                    },
                    backdrop: 'static',
                    size: 'lg',
                    windowClass: 'auto-height',
                }).result.then(function (item) {
                    $rootScope.$broadcast('xxt.float-toolbar.shop.copy', item);
                });
            };
            $rootScope.$on('mattershop.new', function (event, mpid, matter) {
                open(matter, mpid, true);
            });
            $rootScope.$on('mattershop.open', function (event, matter) {
                console.log('bbbbb');
                open(matter, null, false);
            });
        }]
    };
}]);
