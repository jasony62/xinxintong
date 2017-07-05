define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTag',['$scope', '$http', function($scope, $http) {
        $scope.scopeNames = {
            'U': '参与者',
            'I': '发起人'
        };
        $scope.page = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.createTag = function() {
            var newTags;
            if ($scope.model.newtag) {
                newTags = $scope.model.newtag.replace(/\s/, ',');
                newTags = newTags.split(',');
                $http.post('/rest/site/fe/matter/enroll/tag/create?site=' + $scope.app.siteid + '&app=' + $scope.app.id, newTags).then(function(rsp) {
                    rsp.data.forEach(function(oNewTag) {
                        $scope.tags.push(oNewTag);
                    });
                });
                $scope.model.newtag = '';
            }
        };
        $scope.doSearch = function(page) {

        };
        $scope.remove = function(tag, index) {

        };
        $scope.up = function(tag, index) {

        };
        $scope.down = function(tag, index) {

        };
        $scope.save = function() {
            var filter = 'ID:' + $scope.app.id,
                posted = [],
                url, tag;

            for (var k in $scope.tags) {
                tag = $scope.tags[k];
                if (tag.id || tag.content != 0) {
                    var data;
                    data = {
                        act: tag.act,
                        actor_delta: tag.actor_delta,
                        matter_type: 'enroll',
                        matter_filter: filter
                    };
                    tag.id && (data.id = tag.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/matter/enroll/coin/saveRules?site=' + $scope.app.siteid;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.tags[k].id = rsp.data[k];
                }
            });
        };
    }]);
});
