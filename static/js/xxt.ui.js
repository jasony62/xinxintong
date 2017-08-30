angular.module('ui.xxt', []).
constant('matterTypes', [{
    value: 'text',
    title: '文本',
    url: '/rest/pl/fe/matter'
}, {
    value: 'article',
    title: '单图文',
    url: '/rest/pl/fe/matter'
}, {
    value: 'news',
    title: '多图文',
    url: '/rest/pl/fe/matter'
}, {
    value: 'channel',
    title: '频道',
    url: '/rest/pl/fe/matter'
}, {
    value: 'link',
    title: '链接',
    url: '/rest/pl/fe/matter'
}, {
    value: 'enroll',
    title: '登记活动',
    url: '/rest/pl/fe/matter'
}, {
    value: 'signin',
    title: '签到活动',
    url: '/rest/pl/fe/matter'
}, {
    value: 'lottery',
    title: '抽奖活动',
    url: '/rest/pl/fe/matter'
}, {
    value: 'wall',
    title: '信息墙',
    url: '/rest/pl/fe/matter'
}, {
    value: 'joinwall',
    title: '进入信息墙',
    url: '/rest/pl/fe/matter'
}, {
    value: 'contribute',
    title: '投稿活动',
    url: '/rest/pl/fe/matter'
}, {
    value: 'inner',
    title: '内置回复',
    url: '/rest/pl/fe/matter'
}, {
    value: 'relay',
    title: '转发消息',
    url: '/rest/pl/fe/matter'
}, ]).
service('userSetAsParam', [function() {
    this.convert = function(userSet) {
        if (userSet.userScope === '') return [];
        var params = [],
            i, dept, tagIds = [],
            tagNames = [];
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
}]).
filter('typetitle', ['matterTypes', function(matterTypes) {
    return function(type) {
        for (var i in matterTypes) {
            if (type && type.toLowerCase() === matterTypes[i].value)
                return matterTypes[i].title;
        }
        return '';
    }
}]).
factory('mediagallery', function($uibModal) {
    var gallery = {},
        open;
    open = function(galleryId, options) {
        modalInstance = $uibModal.open({
            templateUrl: '/static/template/mediagallery2.html',
            controller: ['$scope', '$uibModalInstance', 'url', function($scope2, $mi, url) {
                $scope2.title = options.mediaType;
                $scope2.url = url;
                $scope2.setshowname = options.setshowname;
                $scope2.setting = {
                    isShowName: 'N'
                };
                $scope2.cancel = function() {
                    $mi.dismiss('cancel');
                };
                $scope2.$watch('setting.isShowName', function(nv) {
                    $mi.isShowName = nv;
                });
            }],
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height media-gallery',
            resolve: {
                url: function() {
                    return "/kcfinder/browse.php?lang=zh-cn&type=" + options.mediaType + "&mpid=" + galleryId;
                },
            }
        });
    };
    gallery.open = function(galleryId, options) {
        options = angular.extend({
            mediaType: "图片",
            callback: null,
            multiple: false,
            setshowname: false
        }, options);
        var kcfCallBack = function(url) {
            window.KCFinder = null;
            options.callback && options.callback(url, modalInstance.isShowName);
            modalInstance.close();
        };
        if (options.multiple) {
            window.KCFinder = {
                callBackMultiple: kcfCallBack
            };
        } else {
            window.KCFinder = {
                callBack: kcfCallBack
            };
        }
        open(galleryId, options);
    };
    return gallery;
}).
controller('AccessControllerUserPickerController', ['$scope', '$uibModalInstance', 'userSetAsParam', function($scope, $mi, userSetAsParam) {
    $scope.userConfig = {
        userScope: ['M']
    };
    $scope.userSet = {};
    $scope.cancel = function() {
        $mi.dismiss();
    };
    $scope.ok = function() {
        var data = {};
        data.userScope = $scope.userSet.userScope;
        data.userSet = userSetAsParam.convert($scope.userSet);
        $mi.close(data);
    };
}]).
controller('AccessControlController', ['$rootScope', '$scope', 'http2', '$timeout', '$uibModal', function($rootScope, $scope, http2, $timeout, $uibModal) {
    var objAuthapis = function() {
        $scope.objAuthapis = angular.copy($scope.authapis);
        var aAuthapis = $scope.obj[$scope.propApis] ? $scope.obj[$scope.propApis].trim() : '';
        aAuthapis = aAuthapis.length === 0 ? [] : aAuthapis.split(',');
        for (var i in $scope.objAuthapis) {
            $scope.objAuthapis[i].checked = aAuthapis.indexOf($scope.objAuthapis[i].authid) !== -1 ? 'Y' : 'N';
        }
    };
    $scope.setAccessControl = function() {
        $scope.updateAccessControl();
        if ($scope.authapis.length === 1) {
            $scope.obj[$scope.propApis] = $scope.obj[$scope.propAccess] === 'Y' ? $scope.authapis[0].authid : '';
            $scope.objAuthapis[0].checked = $scope.obj[$scope.propAccess] === 'Y' ? 'Y' : 'N';
            $scope.updateAuthapis();
        }
    };
    $scope.setAuthapi = function(api) {
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
    $scope.addAcl = function() {
        var newAcl = {
            identity: '',
            idsrc: ''
        };
        $scope.obj[$scope.propAcl].push(newAcl);
        $timeout(function() {
            $('ul.acls li:last-child input').focus();
        }, 10);
    };
    $scope.openAclSelector = function() {
        $uibModal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'AccessControllerUserPickerController',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(data) {
            var i, newAcl, addAcl;
            addAcl = function(rsp) {
                $scope.obj[$scope.propAcl].push(rsp.data);
            };
            for (i in data.userSet) {
                newAcl = data.userSet[i];
                http2.post($scope.changeAclUrl, newAcl, addAcl);
            }
        });
    };
    $scope.clickAcl = function(acl, state, event) {
        if (acl.idsrc.length === 0) {
            state.editing = true;
            var i = $scope.obj[$scope.propAcl].indexOf(acl) + 1;
            $timeout(function() {
                $('ul.acls li:nth-child(' + i + ') input').focus();
            }, 10);
        }
    };
    $scope.changeAcl = function(newAcl, state) {
        http2.post($scope.changeAclUrl, newAcl, function(rsp) {
            if (newAcl.id === undefined)
                newAcl.id = rsp.data.id;
            if (newAcl.idsrc === '') newAcl.label = newAcl.identity;
            state.editing = false;
        });
    };
    $scope.removeAcl = function(acl, event) {
        event.preventDefault();
        event.stopPropagation();
        var i = $scope.obj[$scope.propAcl].indexOf(acl);
        if (acl.id === undefined)
            $scope.obj[$scope.propAcl].splice(i, 1);
        else {
            http2.get($scope.removeAclUrl + '?acl=' + acl.id, function(rsp) {
                $scope.obj[$scope.propAcl].splice(i, 1);
            });
        }
    };
    $scope.$watch('obj', function(obj) {
        if (obj && $scope.authapis) objAuthapis();
    });
    http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
        $scope.authapis = rsp.data;
        if ($scope.obj) objAuthapis();
    });
}]).
directive('accesscontrol', function() {
    return {
        restrict: 'EA',
        scope: {
            title: '@',
            label: '@',
            mpid: '@',
            obj: '=',
            propAcl: '@',
            labelOfList: '@',
            propAccess: '@',
            propApis: '@',
            changeAclUrl: '@',
            removeAclUrl: '@',
            updateAccessControl: '&',
            updateAuthapis: '&',
            labelSpan: '@',
            controlSpan: '@',
            disabled: '@',
            hideAccessControl: '@'
        },
        controller: 'AccessControlController',
        templateUrl: '/static/template/accesscontrol.html?_=9',
    }
}).
directive('userpopover', ['http2', function(http2) {
    return {
        restrict: 'A',
        scope: {
            xxtFid: '@'
        },
        link: function(scope, elem, attrs) {
            $(elem).on('mouseenter', function(event) {
                if (!$(elem).attr('loaded')) {
                    http2.get('/rest/mp/user/fans/get?fid=' + scope.xxtFid, function(rsp) {
                        var member, tags = [],
                            depts = [],
                            detail = '';
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
}]).
controller('SendmeController', ['$scope', 'http2', function($scope, http2) {
    /*不需要了*/
    $scope.qrcodeShown = false;
    $scope.qrcode = function(matter, event) {
        if (!$scope.qrcodeShown) {
            var url = '/rest/mp/call/qrcode/createOneOff';
            url += '?matter_type=' + matter.type;
            url += '&matter_id=' + matter.id;
            if (matter.mpid !== undefined) url += '&mpid=' + matter.mpid;
            http2.get(url, function(rsp) {
                $popover = $(event.target);
                $popover.popover({
                    html: true,
                    title: '<span>扫描发送到手机</span><button class="close" onclick="$popover.popover(\'destroy\')"><span>&times;</span></button>',
                    content: "<div><img src='" + rsp.data.pic + "'></div>"
                });
                $popover.on('hidden.bs.popover', function() {
                    $popover.popover('destroy');
                    $scope.qrcodeShown = false;
                })
                $popover.popover('show');
                $scope.qrcodeShown = true;
            });
        }
    };
}]).
controller('UserPickerController', ['http2', '$scope', function(http2, $scope) {
    var getPickedAuthapi = function() {
        var authid = $scope.userSet.userScope.split('_').pop();
        for (var i in $scope.authapis) {
            if (authid === $scope.authapis[i].authid)
                return $scope.authapis[i];
        }
    };
    $scope.showPickSingleMember = false;
    $scope.isPickSingleMember = 'N';
    $scope.isPickMember = function() {
        return /authid_\d+/.test($scope.userSet.userScope);
    };
    $scope.canGroup = function() {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('G') !== -1;
    };
    $scope.canMember = function() {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('M') !== -1;
    };
    $scope.pickMp = function(mp) {
        !$scope.userSet.childmps && ($scope.userSet.childmps = []);
        if (mp.checked === 'Y')
            $scope.userSet.childmps.push(mp);
        else
            $scope.userSet.childmps.splice($scope.userSet.childmps.indexOf(mp), 1);
    };
    $scope.pickGroup = function(g) {
        !$scope.userSet.fansGroup && ($scope.userSet.fansGroup = []);
        if (g.checked === 'Y')
            $scope.userSet.fansGroup.push(g);
        else
            $scope.userSet.fansGroup.splice($scope.userSet.fansGroup.indexOf(g), 1);
    };
    $scope.$watch('userSet.userScope', function(nv) {
        if (nv && nv.length) {
            if (nv === 'mp') {
                http2.get('/rest/mp/mpaccount/childmps', function(rsp) {
                    $scope.childmps = rsp.data;
                });
            } else if (nv === 'g' && $scope.groups === undefined) {
                http2.get('/rest/mp/user/fans/group', function(rsp) {
                    $scope.groups = rsp.data;
                });
            } else if (/authid_\d+/.test(nv)) {
                $scope.authapi = getPickedAuthapi();
                http2.get($scope.authapi.url + '/memberSelector?authid=' + $scope.authapi.authid, function(rsp) {
                    $.getScript(rsp.data.js, function() {
                        $scope.memberViewUrl = rsp.data.view;
                        $scope.$apply('memberViewUrl');
                    });
                });
            }
        }
    });
    $scope.$on('init.member.selector', function(event, config) {
        if (config && config.showPickSingleMember !== undefined)
            $scope.showPickSingleMember = config.showPickSingleMember;
    });
    $scope.$on('add.dept.member.selector', function(event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.push(dept);
    });
    $scope.$on('remove.dept.member.selector', function(event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.splice($scope.userSet.depts.indexOf(dept), 1);
    });
    $scope.$on('add.tag.member.selector', function(event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.push(tag);
    });
    $scope.$on('remove.tag.member.selector', function(event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.splice($scope.userSet.tags.indexOf(tag), 1);
    });
    $scope.$on('add.member.member.selector', function(event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.push(member);
    });
    $scope.$on('remove.member.member.selector', function(event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.splice($scope.userSet.members.indexOf(member), 1);
    });
    if ($scope.canMember()) {
        http2.get('/rest/member/authapis', function(rsp) {
            $scope.authapis = rsp.data;
        });
    }
}]).
directive('userpicker', ['http2', function(http2) {
    return {
        restrict: 'EA',
        scope: {
            userSet: '=',
            userConfig: '='
        },
        controller: 'UserPickerController',
        templateUrl: function() {
            return '/rest/mp/user/picker';
        },
    };
}]).
factory('pushnotify', ['$uibModal', function($uibModal) {
    return {
        open: function(siteId, callback, options) {
            $uibModal.open({
                templateUrl: '/static/template/pushnotify.html?_=7',
                controller: ['http2', '$scope', '$uibModalInstance', '$q', function(http2, $scope, $mi, $q) {
                    function getLastNotice(sender) {
                        var deferred = $q.defer();
                        http2.get('/rest/pl/fe/matter/tmplmsg/notice/last?sender=' + sender, function(rsp) {
                            deferred.resolve(rsp.data);
                        });
                        return deferred.promise;
                    }
                    var url = '/rest/pl/fe/site/setting/notice/get?site=' + siteId + '&name=site.matter.push&cascaded=Y',
                        msgMatter = {},
                        urlMatterTypes = [],
                        missionId;

                    $scope.options = options;
                    $scope.msgMatter = msgMatter;
                    if (options) {
                        if (options.matterTypes && options.matterTypes.length) {
                            msgMatter.matterType = options.matterTypes[0];
                            options.matterTypes.forEach(function(mt) {
                                mt.value !== 'tmplmsg' && urlMatterTypes.push(mt);
                            });
                        }
                        options.missionId && (missionId = options.missionId);
                    }

                    $scope.urlMatterTypes = urlMatterTypes;
                    //站点设置的素材通知模版
                    http2.get(url, function(rsp) {
                        $scope.tmplmsgConfig = rsp.data.tmplmsgConfig;
                    });
                    $scope.page = {
                        at: 1,
                        size: 10
                    };
                    $scope.message = {};
                    $scope.aChecked = [];
                    $scope.doCheck = function(matter) {
                        $scope.aChecked = [matter];
                        if (msgMatter.matterType.value === 'tmplmsg') {
                            $scope.pickedTmplmsg = matter;
                        } else {
                            (function() {
                                angular.forEach($scope.tmplmsgConfig.mapping, function(mapping, prop) {
                                    if (mapping.src === 'text') {
                                        $scope.message[prop] = mapping.id;
                                    } else {
                                        if (matter[mapping.id] !== undefined) {
                                            $scope.message[prop] = matter[mapping.id];
                                        }
                                    }
                                });
                            })();
                            $scope.message.url = matter.url;
                        }
                    };
                    $scope.doSearch = function() {
                        if (!msgMatter.matterType) return;
                        var matterType = msgMatter.matterType,
                            url = matterType.url;

                        url += '/' + matterType.value;
                        url += '/list?site=' + siteId;
                        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
                        if (matterType.value === 'tmplmsg') {
                            url += '&cascaded=Y';
                        }
                        missionId && (url += '&mission=' + missionId);
                        http2.post(url, {}, function(rsp) {
                            if (/article/.test(matterType.value)) {
                                $scope.matters = rsp.data.articles;
                                $scope.page.total = rsp.data.total;
                            } else if (/contribute|enroll/.test(matterType.value)) {
                                $scope.matters = rsp.data.apps;
                                rsp.data[1] && ($scope.page.total = rsp.data[1]);
                            } else {
                                $scope.matters = rsp.data;
                                $scope.page.total = $scope.matters.length;
                            }
                            if (options.sender) {
                                getLastNotice(options.sender).then(function(lastNotice) {
                                    var i, matter;
                                    for (i = $scope.matters.length - 1; i >= 0; i--) {
                                        matter = $scope.matters[i];
                                        if (lastNotice.tmplmsg_id === matter.id) {
                                            $scope.doCheck(matter);
                                            break;
                                        }
                                    }
                                    if (i >= 0 && lastNotice.params) {
                                        for (var p in lastNotice.params) {
                                            $scope.message[p] = lastNotice.params[p];
                                        }
                                    }
                                });
                            }
                        }, {
                            headers: {
                                'ACCEPT': 'application/json'
                            }
                        });
                    };
                    $scope.urlMatter = {};
                    $scope.page2 = {
                        at: 1,
                        size: 5
                    };
                    $scope.doSearch2 = function() {
                        if (!$scope.urlMatter.matterType) return;
                        var matterType = $scope.urlMatter.matterType,
                            url = matterType.url;

                        url += '/' + matterType.value;
                        url += '/list?site=' + siteId;
                        url += '&page=' + $scope.page2.at + '&size=' + $scope.page2.size;
                        missionId && (url += '&mission=' + missionId);
                        http2.post(url, {}, function(rsp) {
                            if (/article/.test(matterType.value)) {
                                $scope.matters2 = rsp.data.articles;
                                $scope.page2.total = rsp.data.total;
                            } else if (/contribute|enroll/.test(matterType.value)) {
                                $scope.matters2 = rsp.data.apps;
                                rsp.data[1] && ($scope.page2.total = rsp.data[1]);
                            } else {
                                $scope.matters2 = rsp.data;
                                $scope.page2.total = $scope.matters2.length;
                            }
                        }, {
                            headers: {
                                'ACCEPT': 'application/json'
                            }
                        });
                    };
                    $scope.doCheck2 = function(matter) {
                        $scope.urlMatter.selected = matter;
                        $scope.message.url = matter.url;
                    };
                    $scope.changeUrlMatterType = function() {
                        $scope.doSearch2();
                    };
                    $scope.ok = function() {
                        var notify, matterType = msgMatter.matterType;
                        if (matterType.value === 'tmplmsg') {
                            notify = {
                                matters: $scope.aChecked,
                                matterType: matterType ? matterType.value : 'article',
                                tmplmsg: $scope.pickedTmplmsg,
                                message: $scope.message
                            };
                        } else {
                            notify = {
                                matters: $scope.aChecked,
                                matterType: matterType ? matterType.value : 'article',
                                tmplmsg: $scope.tmplmsgConfig.tmplmsg,
                                message: $scope.message
                            };
                        }
                        $mi.close(notify);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.$watch('msgMatter.matterType', function(nv) {
                        $scope.doSearch();
                    });
                }],
                backdrop: 'static',
                size: 'lg',
                windowClass: 'auto-height'
            }).result.then(function(result) {
                callback && callback(result);
            });
        }
    }
}]);