angular.module('ui.xxt', ['ui.bootstrap']).
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
    value: 'addressbook',
    title: '通讯录',
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
    title: '讨论组',
    url: '/rest/pl/fe/matter'
}, {
    value: 'joinwall',
    title: '加入讨论组',
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
factory('mattersgallery', function($uibModal) {
    var gallery = {};
    gallery.open = function(galleryId, callback, options) {
        $uibModal.open({
            templateUrl: '/static/template/mattersgallery2.html?_=6',
            controller: ['$scope', '$http', '$uibModalInstance', function($scope, $http, $mi) {
                var fields = ['id', 'title'];
                $scope.matterTypes = options.matterTypes;
                $scope.singleMatter = options.singleMatter;
                $scope.p = {};
                if ($scope.matterTypes && $scope.matterTypes.length) {
                    $scope.p.matterType = $scope.matterTypes[0];
                }
                if (options.mission) {
                    $scope.mission = options.mission;
                    $scope.p.sameMission = 'Y';
                }
                $scope.page = {
                    current: 1,
                    size: 10
                };
                $scope.aChecked = [];
                $scope.doCheck = function(matter) {
                    if ($scope.singleMatter) {
                        $scope.aChecked = [matter];
                    } else {
                        var i = $scope.aChecked.indexOf(matter);
                        if (i === -1) {
                            $scope.aChecked.push(matter);
                        } else {
                            $scope.aChecked.splice(i, 1);
                        }
                    }
                };
                $scope.doSearch = function(page) {
                    if (!$scope.p.matterType) return;
                    var matter = $scope.p.matterType,
                        url = matter.url,
                        params = {};

                    page && ($scope.page.current = page);
                    url += '/' + matter.value;
                    url += '/list?site=' + galleryId + '&page=' + $scope.page.current + '&size=' + $scope.page.size + '&fields=' + fields;
                    /*指定登记活动场景*/
                    if (matter.value === 'enroll' && matter.scenario) {
                        url += '&scenario=' + matter.scenario;
                    }
                    /*同一个项目*/
                    if ($scope.p.sameMission === 'Y') {
                        url += '&mission=' + $scope.mission.id;
                    }
                    $http.post(url, params).success(function(rsp) {
                        if (/article/.test(matter.value)) {
                            $scope.matters = rsp.data.articles;
                            $scope.page.total = rsp.data.total;
                        } else if (/enroll|signin|group|contribute/.test(matter.value)) {
                            $scope.matters = rsp.data.apps;
                            $scope.page.total = rsp.data.total;
                        } else if (/mission/.test(matter.value)) {
                            $scope.matters = rsp.data.missions;
                            $scope.page.total = rsp.data.total;
                        } else {
                            $scope.matters = rsp.data;
                            $scope.page.total = $scope.matters.length;
                        }
                    });
                };
                $scope.ok = function() {
                    $mi.close([$scope.aChecked, $scope.p.matterType ? $scope.p.matterType.value : 'article']);
                };
                $scope.cancel = function() {
                    $mi.dismiss('cancel');
                };
                $scope.$watch('p.matterType', function(nv) {
                    $scope.doSearch();
                });
            }],
            size: 'lg',
            backdrop: 'static',
            windowClass: 'auto-height mattersgallery'
        }).result.then(function(result) {
            callback && callback(result[0], result[1]);
        });
    };
    return gallery;
}).
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
directive('pushmatter', function() {
    return {
        restrict: 'E',
        scope: {
            matterId: '@',
            matterType: '@',
            mpaccount: '='
        },
        controller: ['$rootScope', '$scope', '$uibModal', 'http2', function($rootScope, $scope, $uibModal, http2) {
            $scope.open = function() {
                $uibModal.open({
                    templateUrl: '/static/template/pushmatter.html?_=5',
                    controller: ['http2', '$scope', '$uibModalInstance', 'userSetAsParam', function(http2, $scope, $uibModalInstance, userSetAsParam) {
                        $scope.userConfig = {
                            userScope: ['M']
                        };
                        $scope.userSet = {};
                        $scope.cancel = function() {
                            $uibModalInstance.dismiss();
                        };
                        $scope.ok = function() {
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

                            $uibModalInstance.close(data);
                        };
                        http2.get('/rest/mp/mpaccount/apis', function(rsp) {
                            if (rsp.data.mpsrc === 'qy' || (rsp.data.mpsrc === 'yx' && rsp.data.yx_p2p))
                                $scope.userConfig.userScope.push('M');
                        });
                    }],
                    backdrop: 'static',
                    size: 'lg',
                    windowClass: 'auto-height'
                }).result.then(function(data) {
                    data.id = $scope.matterId;
                    data.type = $scope.matterType;
                    if (data.mps !== undefined) {
                        var i, mps;
                        i = 0;
                        mps = [];
                        for (i; i < data.mps.length; i++) {
                            mps.push(data.mps[i].mpid);
                        }
                        data.mps = mps;
                        http2.post('/rest/mp/send/mass2mps', data, function(rsp) {
                            $rootScope.infomsg = '发送完成';
                        });
                    } else {
                        if (data.targetUser === 'M' && $scope.mpaccount.mpsrc === 'yx') {
                            var countOfUsers;
                            var doSend = function(phase) {
                                http2.get('/rest/mp/send/yxmember?phase=' + phase, function(rsp) {
                                    if (rsp.data.nextPhase) {
                                        doSend(rsp.data.nextPhase);
                                        $rootScope.progmsg = '正在发送数据，剩余用户：' + rsp.data.countOfOpenids;
                                    } else {
                                        var msg;
                                        msg = '完成向【' + countOfUsers + '】个用户发送';
                                        if (rsp.data.length) {
                                            msg += '，失败【' + JSON.stringify(rsp.data) + '】用户';
                                        }
                                        $rootScope.progmsg = msg;
                                    }
                                });
                            }
                            http2.post('/rest/mp/send/yxmember', data, function(rsp) {
                                if (rsp.data.nextPhase) {
                                    doSend(rsp.data.nextPhase);
                                    countOfUsers = rsp.data.countOfOpenids;
                                    $rootScope.progmsg = '正在发送数据，剩余用户：' + countOfUsers;
                                } else {
                                    var msg;
                                    msg = '完成向【' + countOfUsers + '】个用户发送';
                                    if (rsp.data.length) {
                                        msg += '，失败【' + JSON.stringify(rsp.data) + '】用户';
                                    }
                                    $rootScope.progmsg = msg;
                                }
                            });
                        } else {
                            http2.post('/rest/mp/send/mass', data, function(rsp) {
                                $rootScope.infomsg = '发送完成';
                            });
                        }
                    }
                });
            };
        }],
        replace: true,
        transclude: true,
        template: "<button ng-click='open()' ng-transclude></button>",
    };
}).
directive('pushnotify', function() {
    return {
        restrict: 'E',
        scope: {
            site: '@',
            singleMatter: '@',
            matterTypes: '=',
        },
        controller: ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
            $scope.open = function() {
                $uibModal.open({
                    templateUrl: '/static/template/pushnotify.html?_=1',
                    resolve: {
                        options: function() {
                            return {
                                site: $scope.site,
                                singleMatter: $scope.singleMatter ? $scope.singleMatter : 'N',
                                matterTypes: $scope.matterTypes
                            };
                        }
                    },
                    controller: ['http2', '$scope', '$uibModalInstance', 'options', function(http2, $scope, $mi, options) {
                        $scope.options = options;
                        $scope.p = {};
                        options.matterTypes && options.matterTypes.length && ($scope.p.matterType = options.matterTypes[0]);
                        var fields = ['id', 'title'];
                        $scope.page = {
                            current: 1,
                            size: 10
                        };
                        $scope.aChecked = [];
                        $scope.doCheck = function(matter) {
                            if (options.singleMatter === 'Y') {
                                $scope.aChecked = [matter];
                            } else {
                                var i = $scope.aChecked.indexOf(matter);
                                i === -1 ? $scope.aChecked.push(matter) : $scope.aChecked.splice(i, 1);
                            }
                        };
                        $scope.doSearch = function() {
                            if (!$scope.p.matterType) return;
                            var url, params = {};
                            url = $scope.p.matterType.url;
                            url += '/' + $scope.p.matterType.value;
                            url += '/get?site=' + options.site + 'page=' + $scope.page.current + '&size=' + $scope.page.size + '&fields=' + fields;
                            http2.post(url, params, function(rsp) {
                                if (/article/.test($scope.p.matterType.value)) {
                                    $scope.matters = rsp.data.articles;
                                    $scope.page.total = rsp.data.total;
                                } else if (/contribute|enroll/.test($scope.p.matterType.value)) {
                                    $scope.matters = rsp.data.articles;
                                    rsp.data[1] && ($scope.page.total = rsp.data[1]);
                                } else {
                                    $scope.matters = rsp.data;
                                    $scope.page.total = $scope.matters.length;
                                }
                            }, {
                                headers: {
                                    'ACCEPT': 'application/json'
                                }
                            });
                        };
                        $scope.ok = function() {
                            $mi.close([$scope.aChecked, $scope.p.matterType ? $scope.p.matterType.value : 'article']);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                        $scope.$watch('p.matterType', function(nv) {
                            $scope.doSearch();
                        });
                    }],
                    backdrop: 'static',
                    size: 'lg',
                    windowClass: 'auto-height'
                }).result.then(function(data) {
                    $scope.$emit('pushnotify.xxt.done', data);
                });
            };
        }],
        replace: true,
        transclude: true,
        template: "<button ng-click='open()' ng-transclude></button>",
    };
}).factory('pushnotify', ['$uibModal', function($uibModal) {
    return {
        open: function(siteId, callback, options) {
            $uibModal.open({
                templateUrl: '/static/template/pushnotify.html?_=2',
                controller: ['http2', '$scope', '$uibModalInstance', function(http2, $scope, $mi) {
                    var fields = 'id,title',
                        url = '/rest/pl/fe/site/setting/notice/get?site=' + siteId + '&name=site.matter.push&cascaded=Y';

                    $scope.options = options;
                    $scope.p = {};
                    options.matterTypes && options.matterTypes.length && ($scope.p.matterType = options.matterTypes[0]);
                    //站点设置的蔬菜素材通知模版
                    http2.get(url, function(rsp) {
                        $scope.tmplmsgConfig = rsp.data.tmplmsgConfig;
                    });
                    $scope.page = {
                        current: 1,
                        size: 10
                    };
                    $scope.message = {};
                    $scope.aChecked = [];
                    $scope.doCheck = function(matter) {
                        if (options.singleMatter === 'Y') {
                            $scope.aChecked = [matter];
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
                        } else {
                            var i = $scope.aChecked.indexOf(matter);
                            i === -1 ? $scope.aChecked.push(matter) : $scope.aChecked.splice(i, 1);
                        }
                    };
                    $scope.doSearch = function() {
                        if (!$scope.p.matterType) return;
                        var url, params = {};
                        url = $scope.p.matterType.url;
                        url += '/' + $scope.p.matterType.value;
                        url += '/list?site=' + siteId + '&page=' + $scope.page.current + '&size=' + $scope.page.size + '&fields=' + fields;
                        http2.post(url, params, function(rsp) {
                            if (/article/.test($scope.p.matterType.value)) {
                                $scope.matters = rsp.data.articles;
                                $scope.page.total = rsp.data.total;
                            } else if (/contribute|enroll/.test($scope.p.matterType.value)) {
                                $scope.matters = rsp.data.articles;
                                rsp.data[1] && ($scope.page.total = rsp.data[1]);
                            } else {
                                $scope.matters = rsp.data;
                                $scope.page.total = $scope.matters.length;
                            }
                        }, {
                            headers: {
                                'ACCEPT': 'application/json'
                            }
                        });
                    };
                    $scope.ok = function() {
                        var notify = {
                            matters: $scope.aChecked,
                            matterType: $scope.p.matterType ? $scope.p.matterType.value : 'article',
                            tmplmsg: $scope.tmplmsgConfig.tmplmsg,
                            message: $scope.message
                        };
                        $mi.close(notify);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                    $scope.$watch('p.matterType', function(nv) {
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