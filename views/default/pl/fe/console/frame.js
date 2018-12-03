define(['require', 'frame/RouteParam', 'frame/const'], function(require, RouteParam, CstApp) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt', 'tmplshop.ui.xxt', 'pl.const', 'service.matter', 'page.ui.xxt', 'modal.ui.xxt', 'schema.ui.xxt', 'ui.xxt']);
    ngApp.constant('cstApp', CstApp);
    ngApp.config(['$controllerProvider', '$provide', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', function($controllerProvider, $provide, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider) {
        ngApp.provider = {
            controller: $controllerProvider.register,
            directive: $compileProvider.directive,
            service: $provide.service
        };
        $locationProvider.html5Mode(true);
        $routeProvider
            .when('/rest/pl/fe/friend', new RouteParam('friend'))
            .when('/rest/pl/fe/users', new RouteParam('users'))
            .otherwise(new RouteParam('main'));
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
    }]);
    ngApp.controller('ctrlFrame', ['$scope', '$location', 'http2', 'srvUserNotice', '$uibModal', 'cstApp', function($scope, $location, http2, srvUserNotice, $uibModal, cstApp) {
        var _oFrameState;
        _oFrameState = {
            sid: '',
            view: '',
            scope: ''
        };
        $scope.opened = '';
        /* 设置页面入口状态 */
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/[^\/]+$/)[0];
            subView.indexOf('?') !== -1 && (subView = subView.substr(0, subView.indexOf('?')));
            subView = subView === 'fe' ? 'main' : subView;
            if (subView !== _oFrameState.view) {
                _oFrameState.view = subView;
                if (_oFrameState.view === 'main') {
                    _oFrameState.scope = 'mission';
                } else if (_oFrameState.view === 'users') {
                    _oFrameState.scope = 'account';
                } else if (_oFrameState.view === 'friend') {
                    _oFrameState.scope = 'subscribeSite';
                }
            }
            switch (_oFrameState.scope) {
                case 'mission':
                case 'activity':
                case 'doc':
                case 'recycle':
                    $scope.opened = 'main';
                    break;
                case 'account':
                case 'member':
                case 'subscriber':
                    $scope.opened = 'users';
                    break;
                case 'subscribeSite':
                case 'contributeSite':
                case 'favorSite':
                    $scope.opened = 'friend';
                    break;
                default:
                    $scope.opened = '';
            }
        });
        var url = '/rest/pl/fe/user/get?_=' + (new Date * 1);
        http2.get(url).then(function(rsp) {
            $scope.loginUser = rsp.data;
        });
        $scope.getMatterTag = function() {
            http2.get('/rest/pl/fe/matter/tag/listTags?site=' + _oFrameState.sid).then(function(rsp) {
                $scope.tagsMatter = rsp.data;
            });
        };
        $scope.closeNotice = function(log) {
            srvUserNotice.closeNotice(log).then(function(rsp) {
                $scope.notice.logs.splice($scope.notice.logs.indexOf(log), 1);
                $scope.notice.page.total--;
            });
        };
        srvUserNotice.uncloseList().then(function(result) {
            $scope.notice = result;
        });
        $scope.changeScope = function(scope) {
            _oFrameState.scope = scope;
            switch (scope) {
                case 'mission':
                case 'activity':
                case 'doc':
                case 'recycle':
                    $location.url('/rest/pl/fe');
                    break;
                case 'account':
                case 'member':
                case 'subscriber':
                    $location.url('/rest/pl/fe/users');
                    $scope.opened = 'users';
                    break;
                case 'subscribeSite':
                case 'contributeSite':
                case 'favorSite':
                    $location.url('/rest/pl/fe/friend');
                    break;
            }
        };
        $scope.openSite = function(id) {
            location.href = '/rest/pl/fe/site?site=' + id;
        };
        $scope.createSite = function() {
            location.href = '/rest/pl/fe/site/plan';
        };
        /*新建素材*/
        var _fns = {
            addLink: function(site) {
                http2.get('/rest/pl/fe/matter/link/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/link?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addArticle: function(site) {
                http2.get('/rest/pl/fe/matter/article/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/article?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addNews: function(site) {
                http2.get('/rest/pl/fe/matter/news/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/news?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addChannel: function(site) {
                http2.get('/rest/pl/fe/matter/channel/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/channel?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addEnroll: function(site) {
                location.href = '/rest/pl/fe/matter/enroll/shop?site=' + site.id;
            },
            addSignin: function(site) {
                location.href = '/rest/pl/fe/matter/signin/plan?site=' + site.id;
            },
            addGroup: function(site) {
                location.href = '/rest/pl/fe/matter/group/plan?site=' + site.id;
            },
            addLottery: function(site) {
                http2.get('/rest/pl/fe/matter/lottery/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/lottery?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addCustom: function(site) {
                http2.get('/rest/pl/fe/matter/custom/create?site=' + site.id).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/custom?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addWall: function(site, scenario) {
                location.href = '/rest/pl/fe/matter/wall/shop?site=' + site.id + '&scenario=' + (scenario || '');
            },
            addText: function(site) {
                location.href = '/rest/pl/fe/matter/text?site=' + site.id;
            }
        };

        function addMatter(site, matterType) {
            var fnName = 'add' + matterType[0].toUpperCase() + matterType.substr(1);
            _fns[fnName].call(_fns, site);
        }
        $scope.addMatter = function(matterType) {
            if (_oFrameState.sid && matterType) {
                var site = { id: _oFrameState.sid };
                addMatter(site, matterType);
            }
        };

        $scope.listSite = function() {
            var url, oPlSite;
            url = '/rest/pl/fe/site/list';
            oPlSite = { id: '_coworker', name: '被邀合作项目' };
            http2.get(url + '?_=' + (new Date * 1)).then(function(rsp) {
                var userSites;
                $scope.sites = userSites = rsp.data;
                userSites.splice(0, 0, oPlSite);
                /* 恢复上一次访问的状态 */
                if (window.localStorage) {
                    $scope.$watch('frameState', function(nv) {
                        if (nv) {
                            window.localStorage.setItem("pl.fe.frameState", JSON.stringify(nv));
                        }
                    }, true);
                    if (_oFrameState = window.localStorage.getItem("pl.fe.frameState")) {
                        _oFrameState = JSON.parse(_oFrameState);
                    }
                }
                /* 通过参数指定的状态 */
                var lsearch
                lsearch = $location.search();
                if (lsearch.sid) {
                    _oFrameState.sid = lsearch.sid;
                }
                if (lsearch.view) {
                    _oFrameState.view = lsearch.view;
                    if (lsearch.scope) {
                        _oFrameState.scope = lsearch.scope;
                    }
                }
                var bSiteExistent;
                if (_oFrameState.sid) {
                    for (var i = 0, ii = userSites.length; i < ii; i++) {
                        if (_oFrameState.sid === userSites[i].id) {
                            bSiteExistent = true;
                            break;
                        }
                    }
                    if (!bSiteExistent) {
                        _oFrameState.sid = '';
                    }
                }
                if (!_oFrameState.sid) {
                    _oFrameState.sid = userSites[0].id;
                }
                $scope.frameState = _oFrameState;
            });
        };
        $scope.matterTagsFram = function(filter, filter2) {
            var oTags, tagsOfData;
            tagsOfData = filter2.byTags;
            oTags = $scope.tagsMatter;
            $uibModal.open({
                templateUrl: 'tagMatterData.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var model;
                    $scope2.apptags = oTags;
                    $scope2.model = model = {
                        selected: []
                    };
                    if (tagsOfData) {
                        tagsOfData.forEach(function(oTag) {
                            var index;
                            if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                                model.selected[$scope2.apptags.indexOf(oTag)] = true;
                            }
                        });
                    }
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var addMatterTag = [];
                        model.selected.forEach(function(selected, index) {
                            if (selected) {
                                addMatterTag.push($scope2.apptags[index]);
                            }
                        });
                        filter2.byTags = addMatterTag;
                        angular.extend(filter, filter2);
                        $mi.close();
                    };
                }],
                backdrop: 'static',
            });
        };
        $scope.listSite();
        var isNavCollapsed = false;
        if (document.body.clientWidth <= 768) {
            isNavCollapsed = true;
        }
        $scope.isNavCollapsed = isNavCollapsed;
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});