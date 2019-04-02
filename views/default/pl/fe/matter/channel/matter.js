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
        var _oEditing, _oPage;
        $scope.matterTypes = cstApp.matterTypes;
        $scope.page = _oPage = {
            at: 1,
            size: 10,
            j: function() {
                return '?page=' + this.at + '&size=' + this.size;
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
        $scope.gotoMatter = function(matter) {
            location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.siteId + '&id=' + matter.id;
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
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            _oEditing = nv;
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=top').then(function(data) {
                $scope.topMatters = data;
            });
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=center' + page.j()).then(function(data) {
                $scope.topMatters = data.matters;
                $scope.page.total = data.total;
            });
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=bottom').then(function(data) {
                $scope.bottomMatters = data;
            });
        });
    }]);
});