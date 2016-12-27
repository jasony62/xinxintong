(function() {
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
        var actions = [{
            name: 'site.matter.article.read',
            desc: '用户A阅读用户B的投稿'
        },{
            name: 'site.matter.article.like',
            desc: '用户A点赞用户B的投稿'
        },{
            name: 'site.matter.article.approved',
            desc: '用户B的投稿通过审核'
        },{
            name: 'site.matter.article.share.friend',
            desc: '用户A分享用户B的投稿给好友'
        },{
            name: 'site.matter.article.share.timeline',
            desc: '用户A分享用户B的投稿到朋友圈'
        }];
        $scope.$parent.subView = 'coin';
        $scope.rules = {};
        angular.forEach(actions, function(act) {
            var name;
            name = act.name;
            $scope.rules[name] = {
                act: name,
                desc: act.desc,
                actor_delta: 0,
                creator_delta: 0,
            };
        });
        $scope.save = function() {
            var filter = 'ENTRY:contribute,' + $scope.id,
                posted = [],
                url, rule;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.id || rule.actor_delta != 0 || rule.creator_delta != 0) {
                    var data;
                    data = {
                        act: rule.act,
                        actor_delta: rule.actor_delta,
                        creator_delta: rule.creator_delta,
                        matter_type: 'article',
                        matter_filter: filter
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/matter/contribute/coin/save?site=' + $scope.siteId + '&app=' + $scope.id;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
            });
        };
        $scope.fetch = function() {
            var url;
            url = '/rest/pl/fe/matter/contribute/coin/get?app=' + $scope.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(rule) {
                    var rule2 = $scope.rules[rule.act];
                    rule2.id = rule.id;
                    rule2.actor_delta = rule.actor_delta;
                    rule2.creator_delta = rule.creator_delta;
                });
            });
        };
        $scope.fetch();
    }]);
})();