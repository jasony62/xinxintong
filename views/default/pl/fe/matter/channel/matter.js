define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMatter', ['$scope', 'http2', 'mediagallery', 'srvSite', '$uibModal', 'cstApp', function($scope, http2, mediagallery, srvSite, $uibModal, cstApp) {
        var _oEditing, _oPage;
        $scope.matterTypes = cstApp.matterTypes;
        $scope.page = _oPage = {
            at: 1,
            size: 12
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
        $scope.setFixed = function(pos) {
            srvSite.openGallery({
                matterTypes: $scope.matterTypes
            }).then(function(result) {
                if (result.matters && result.matters.length) {
                    result.matters.forEach(function(matter) {
                        matter.type = result.type;
                    });
                    http2.post('/rest/pl/fe/matter/channel/setfixed?site=' + $scope.siteId + '&id=' + $scope.id + '&pos=' + pos, result.matters).then(function(rsp) {
                        if (pos === 'top') {
                            $scope.topMatters = rsp.data.matters;
                        } else if (pos === 'bottom') {
                            $scope.bottomMatters = rsp.data.matters;
                        }
                    });
                }
            });
        };
        $scope.cancelFixed = function(matter, pos) {
            var cancled = {
                id: matter.id,
                type: matter.type.toLowerCase()
            }
            http2.post('/rest/pl/fe/matter/channel/unfixed?site=' + $scope.siteId + '&id=' + $scope.id + '&pos=' + pos, cancled).then(function(rsp) {
                if (pos === 'top') {
                    $scope.topMatters = rsp.data.matters;
                } else if (pos === 'bottom') {
                    $scope.bottomMatters = rsp.data.matters;
                }
                $scope.getMatters();
            });
        }
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
                        if (rsp.data === 'ok') {
                            $scope.getMatters();
                        }
                    });
                }
            });
        };
        $scope.removeMatter = function(matter) {
            var removed = {
                id: matter.id,
                type: matter.type.toLowerCase()
            };
            http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + $scope.siteId + '&id=' + $scope.id, removed).then(function(rsp) {
                $scope.getMatters();
            });
        };
        $scope.gotoMatter = function(matter) {
            location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.siteId + '&id=' + matter.id;
        };
        $scope.getTopMatters = function() {
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=top').then(function(rsp) {
                $scope.topMatters = rsp.data.matters;
            });
        };
        $scope.getMatters = function(pageAt) {
            pageAt && (_oPage.at = pageAt);
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=center' + '&page=' + _oPage.at + '&size=' + _oPage.size).then(function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.getBottomMatters = function() {
            http2.get('/rest/pl/fe/matter/channel/mattersList?site=' + _oEditing.siteid + '&id=' + _oEditing.id + '&weight=bottom').then(function(rsp) {
                $scope.bottomMatters = rsp.data.matters;
            });
        };
        $scope.$on('my-sorted', function(ev, val) {
            // rearrange $scope.items
            $scope.topMatters.splice(val.to, 0, $scope.topMatters.splice(val.from, 1)[0]);
            for (var i = 0; i < $scope.topMatters.length; i++) {
                $scope.topMatters[i].seq = i;
            }
            http2.post('/rest/pl/fe/matter/channel/sortMatters?id=' + _oEditing.id + '&weight=top', $scope.topMatters).then(function(rsp) {});
        });
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            _oEditing = nv;
            $scope.getMatters();
            $scope.getTopMatters();
            $scope.getBottomMatters();
        });
    }]);
});