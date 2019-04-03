define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', 'srvSite', '$uibModal', 'srvTag', 'cstApp', function($scope, http2, mediagallery, srvSite, $uibModal, srvTag, cstApp) {
        var _oEditing;
        $scope.matterTypes = cstApp.matterTypes;
        $scope.acceptMatterTypes = cstApp.acceptMatterTypes;
        $scope.volumes = ['1', '2', '3', '4', '5', '6', '7', '8'];
        $scope.gotoMatter = function(matter) {
            location.href = '/rest/pl/fe/matter/' + matter.type + '?site=' + $scope.siteId + '&id=' + matter.id;
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
                },{
                    value: 'article',
                    title: '单图文',
                    url: '/rest/pl/fe/matter'
                }, {
                    value: 'channel',
                    title: '频道',
                    url: '/rest/pl/fe/matter'
                }, {
                    value: 'link',
                    title: '链接',
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
                        title: result.matters[0].title,
                        type: result.matters[0].type,
                        siteid: result.matters[0].siteid
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
            $scope.matters = _oEditing.matters;
        });
    }]);
});