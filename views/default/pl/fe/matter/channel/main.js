define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSite', 'noticebox', '$uibModal', 'srvTag', 'cstApp', function($scope, http2, srvSite, noticebox, $uibModal, srvTag, cstApp) {
        function arrangeMatters() {
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
        }

        function postFixed(pos, params) {
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
        }

        $scope.matterTypes = cstApp.matterTypes;
        $scope.acceptMatterTypes = cstApp.acceptMatterTypes;
        $scope.volumes = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.siteId + '&type=channel&id=' + $scope.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        }
        $scope.update = function(name) {
            var modifiedData = {};
            modifiedData[name] = $scope.editing[name];
            http2.post('/rest/pl/fe/matter/channel/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
                if (name === 'orderby') {
                    http2.get('/rest/pl/fe/matter/channel/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
                        $scope.editing = rsp.data;
                    });
                }
            });
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/channel/remove?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
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
                    http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId + '&channel=' + $scope.editing.id, relations, function(rsp) {
                        $scope.editing.matters = rsp.data;
                        arrangeMatters();
                    });
                }
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
        $scope.tagMatter = function(subType) {
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
});