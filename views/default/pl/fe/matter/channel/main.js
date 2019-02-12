define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', 'srvSite', '$uibModal', 'srvTag', 'cstApp', function($scope, http2, mediagallery, srvSite, $uibModal, srvTag, cstApp) {
        function arrangeMatters() {
            $scope.matters = _oEditing.matters;
            if (_oEditing.top_type) {
                $scope.topMatter = $scope.matters[0];
                $scope.matters = $scope.matters.slice(1);
            } else {
                $scope.topMatter = false;
            }
            if (_oEditing.bottom_type) {
                var l = $scope.matters.length;
                $scope.bottomMatter = $scope.matters[l - 1];
                $scope.matters = $scope.matters.slice(0, l - 1);
            } else {
                $scope.bottomMatter = false;
            }
        }

        function postFixed(pos, params) {
            http2.post('/rest/pl/fe/matter/channel/setfixed?site=' + $scope.siteId + '&id=' + $scope.id + '&pos=' + pos, params).then(function(rsp) {
                if (pos === 'top') {
                    _oEditing.top_type = params.t;
                    _oEditing.top_id = params.id;
                } else if (pos === 'bottom') {
                    _oEditing.bottom_type = params.t;
                    _oEditing.bottom_id = params.id;
                }
                _oEditing.matters = rsp.data;
                arrangeMatters();
            });
        }
        var _oEditing;
        $scope.matterTypes = cstApp.matterTypes;
        $scope.acceptMatterTypes = cstApp.acceptMatterTypes;
        $scope.volumes = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        $scope.update = function(name) {
            var modifiedData = {};
            modifiedData[name] = _oEditing[name];
            http2.post('/rest/pl/fe/matter/channel/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData).then(function() {
                if (name === 'orderby') {
                    http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id).then(function(rsp) {
                        _oEditing = rsp.data;
                    });
                }
            });
        };
        $scope.removePic = function() {
            _oEditing.pic = '';
            $scope.update('pic');
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    _oEditing.pic = url + '?_=' + (new Date * 1);
                    $scope.update('pic');
                }
            };
            mediagallery.open(_oEditing.siteid, options);
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/channel/remove?site=' + $scope.siteId + '&id=' + $scope.id).then(function(rsp) {
                    location = '/rest/pl/fe';
                });
            }
        };
        $scope.setFixed = function(pos, clean) {
            if (!clean) {
                srvSite.openGallery({
                    matterTypes: $scope.matterTypes,
                    singleMatter: true
                }).then(function(result) {
                    if (result.matters.length === 1) {
                        var params = {
                            t: result.type,
                            id: result.matters[0].id
                        };
                        postFixed(pos, params);
                    }
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
            srvSite.openGallery({
                matterTypes: $scope.matterTypes
            }).then(function(result) {
                var relations;
                if (result.matters && result.matters.length) {
                    result.matters.forEach(function(matter) {
                        matter.type = result.type;
                    });
                    relations = { matter: result.matters };
                    http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + _oEditing.id, relations).then(function(rsp) {
                        _oEditing.matters = rsp.data;
                        arrangeMatters();
                    });
                }
            });
        };
        $scope.createArticle = function() {
            http2.get('/rest/pl/fe/matter/article/create?site=' + $scope.siteId).then(function(rsp) {
                var article = rsp.data,
                    relations = { matter: [article] };
                http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + _oEditing.id, relations).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + article.id;
                });
            });
        };
        $scope.createLink = function() {
            http2.get('/rest/pl/fe/matter/link/create?site=' + $scope.siteId).then(function(rsp) {
                var link = rsp.data,
                    relations = { matter: [link] };
                http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + _oEditing.id, relations).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/link?site=' + $scope.siteId + '&id=' + link.id;
                });
            });
        };
        $scope.removeMatter = function(matter) {
            var removed = {
                id: matter.id,
                type: matter.type.toLowerCase()
            };
            http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + $scope.siteId + '&reload=Y&id=' + $scope.id, removed).then(function(rsp) {
                _oEditing.matters = rsp.data;
                arrangeMatters();
            });
        };
        $scope.gotoMatter = function(matter) {
            location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.siteId + '&id=' + matter.id;
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter(_oEditing, oTags, subType);
        };
        $scope.assignNavApp = function() {
            var oOptions = {
                matterTypes: [{
                    value: 'enroll',
                    title: '记录活动',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true
            };
            srvSite.openGallery(oOptions).then(function(result) {
                if (result.matters && result.matters.length === 1) {
                    !_oEditing.config.nav && (_oEditing.config.nav = {});
                    !_oEditing.config.nav.app && (_oEditing.config.nav.app = []);
                    _oEditing.config.nav.app.push({
                        id: result.matters[0].id,
                        title: result.matters[0].title
                    });
                    $scope.update('config');
                }
            });
        };
        $scope.removeNavApp = function(index) {
            _oEditing.config.nav.app.splice(index, 1);
            if (_oEditing.config.nav.app.length === 0) {
                delete _oEditing.config.nav.app;
            }
            $scope.update('config');
        };
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            _oEditing = nv;
            arrangeMatters();
        });
    }]);
});