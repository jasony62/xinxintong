var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
ngApp.config(['$routeProvider', '$locationProvider', 'srvSiteProvider', 'srvTagProvider', function($routeProvider, $locationProvider, srvSiteProvider, srvTagProvider) {
    $routeProvider.otherwise({
        templateUrl: '/views/default/pl/fe/matter/channel/main.html?_=2',
        controller: 'ctrlMain'
    });
    var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
    srvSiteProvider.config(siteId);
    srvTagProvider.config(siteId);
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlChannel', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
    var ls = $location.search();
    $scope.id = ls.id;
    $scope.siteId = ls.site;
    srvSite.get().then(function(oSite) {
        $scope.site = oSite;
    });
    srvSite.tagList().then(function(oTag) {
        $scope.oTag = oTag;
    });
    http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
        if(rsp.data.matter_mg_tag !== ''){
            rsp.data.matter_mg_tag.forEach(function(cTag,index){
                $scope.oTag.forEach(function(oTag){
                    if(oTag.id === cTag){
                        rsp.data.matter_mg_tag[index] = oTag;
                    }
                });
            });
        }
        $scope.editing = rsp.data;
        $scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + $scope.id + '&type=channel';
    });
}]);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'mattersgallery', 'noticebox', '$uibModal', 'srvTag', function($scope, http2, mattersgallery, noticebox, $uibModal, srvTag) {
    var modifiedData = {};
    $scope.modified = false;
    $scope.matterTypes = [{
        value: 'article',
        title: '单图文',
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
        value: 'link',
        title: '链接',
        url: '/rest/pl/fe/matter'
    }, {
        value: 'mission',
        title: '项目',
        url: '/rest/pl/fe/matter'
    }];
    $scope.acceptMatterTypes = [{
        name: '',
        title: '任意'
    }, {
        name: 'article',
        title: '单图文'
    }, {
        name: 'contribute',
        title: '投稿活动'
    }];
    $scope.volumes = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    var arrangeMatters = function() {
        $scope.matters = $scope.editing.matters;
        if ($scope.editing.top_type) {
            $scope.topMatter = $scope.matters[0];
            $scope.matters = $scope.matters.slice(1);
        } else {
            $scope.topMatter = false;
        }
        if ($scope.editing.bottom_type) {
            var l = $scope.matters.length;
            $scope.bottomMatter = $scope.matters[l - 1];
            $scope.matters = $scope.matters.slice(0, l - 1);
        } else {
            $scope.bottomMatter = false;
        }
    };
    var postFixed = function(pos, params) {
        http2.post('/rest/pl/fe/matter/channel/setfixed?site=' + $scope.siteId + '&id=' + $scope.id + '&pos=' + pos, params, function(rsp) {
            if (pos === 'top') {
                $scope.editing.top_type = params.t;
                $scope.editing.top_id = params.id;
            } else if (pos === 'bottom') {
                $scope.editing.bottom_type = params.t;
                $scope.editing.bottom_id = params.id;
            }
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    $scope.submit = function(name) {
        http2.post('/rest/pl/fe/matter/channel/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
            modifiedData = {};
            $scope.modified = false;
            if (name === 'orderby') {
                http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
                    $scope.editing = rsp.data;
                });
            }
        });
    };
    $scope.applyToHome = function() {
        var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.siteId + '&type=channel&id=' + $scope.id;
        http2.get(url, function(rsp) {
            noticebox.success('完成申请！');
        });
    }
    $scope.update = function(name) {
        $scope.modified = true;
        modifiedData[name] = $scope.editing[name];
    };
    $scope.setFixed = function(pos, clean) {
        if (!clean) {
            mattersgallery.open($scope.siteId, function(matters, type) {
                if (matters.length === 1) {
                    var params = {
                        t: type,
                        id: matters[0].id
                    };
                    postFixed(pos, params);
                }
            }, {
                matterTypes: $scope.matterTypes,
                singleMatter: true
            });
        } else {
            var params = {
                t: null,
                id: null
            };
            postFixed(pos, params);
        }
    };
    $scope.addMatter = function() {
        mattersgallery.open($scope.siteId, function(matters, type) {
            var relations;
            if (matters && matters.length) {
                matters.forEach(function(matter) {
                    matter.type = type;
                });
                relations = { matter: matters };
                http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + $scope.editing.id, relations, function(rsp) {
                    $scope.editing.matters = rsp.data;
                    arrangeMatters();
                });
            }
        }, {
            matterTypes: $scope.matterTypes,
        });
    };
    $scope.createArticle = function() {
        http2.get('/rest/pl/fe/matter/article/create?site=' + $scope.siteId, function(rsp) {
            var article = rsp.data,
                relations = { matter: [article] };
            http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + $scope.editing.id, relations, function(rsp) {
                location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + article.id;
            });
        });
    };
    $scope.createLink = function() {
        http2.get('/rest/pl/fe/matter/link/create?site=' + $scope.siteId, function(rsp) {
            var link = rsp.data,
                relations = { matter: [link] };
            http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + $scope.editing.id, relations, function(rsp) {
                location.href = '/rest/pl/fe/matter/link?site=' + $scope.siteId + '&id=' + link.id;
            });
        });
    };
    $scope.removeMatter = function(matter) {
        var removed = {
            id: matter.id,
            type: matter.type.toLowerCase()
        };
        http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + $scope.siteId + '&reload=Y&id=' + $scope.id, removed, function(rsp) {
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    $scope.gotoMatter = function(matter) {
        location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.siteId + '&id=' + matter.id;
    };
    $scope.editPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        var prop = page + '_page_name',
            codeName = $scope.editing[prop];
        if (codeName && codeName.length) {
            location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
        } else {
            http2.get('/rest/pl/fe/matter/channel/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
                $scope.editing[prop] = rsp.data.name;
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
            });
        }
    };
    $scope.resetPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
            var codeName = $scope.editing[page + '_page_name'];
            if (codeName && codeName.length) {
                http2.get('/rest/pl/fe/matter/channel/pageReset?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
                    location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
                });
            } else {
                http2.get('/rest/pl/fe/matter/channel/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
                    $scope.editing[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                });
            }
        }
    };
    $scope.tagMatter = function(subType){
        var oTags;
        oTags = $scope.oTag;
        srvTag._tagMatter($scope.editing, oTags, subType);
    };
    $scope.$watch('editing', function(nv) {
        if (!nv) return;
        arrangeMatters();
    });
    (function() {
        new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
    })();
}]);
