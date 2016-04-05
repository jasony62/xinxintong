(function() {
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
        var prefix = 'app.contribute,' + $scope.id,
            actions = [{
                name: 'article.submit',
                desc: '用户A投稿图文第一次送审核'
            }, {
                name: 'article.approved',
                desc: '用户A投稿图文审核通过',
            }, {
                name: 'article.read',
                desc: '用户B阅读用户A投稿的图文',
            }, {
                name: 'article.remark',
                desc: '用户B评论用户A投稿的图文',
            }, {
                name: 'article.appraise',
                desc: '用户B点赞用户A投稿的图文',
            }, {
                name: 'article.share.F',
                desc: '用户B转发用户A投稿的图文',
            }, {
                name: 'article.share.T',
                desc: '用户B分享朋友圈用户A投稿的图文',
            }];
        $scope.$parent.subView = 'coin';
        $scope.rules = {};
        angular.forEach(actions, function(act) {
            var name;
            name = prefix + '.' + act.name;
            $scope.rules[name] = {
                act: name,
                desc: act.desc,
                delta: 0
            };
        });
        $scope.save = function() {
            var posted, url;
            posted = [];
            angular.forEach($scope.rules, function(rule) {
                if (rule.id || rule.delta != 0) {
                    var data;
                    data = {
                        act: rule.act,
                        delta: rule.delta,
                        objid: '*'
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            });
            url = '/rest/pl/fe/matter/contribute/coin/save?site=' + $scope.siteId + '&app=' + $scope.id;
            http2.post(url, posted, function(rsp) {
                $scope.$root.infomsg = '保存成功';
                angular.forEach(rsp.data, function(id, act) {
                    $scope.rules[act].id = id;
                });
            });
        };
        $scope.fetch = function() {
            var url;
            url = '/rest/pl/fe/matter/contribute/coin/get?app=' + $scope.id;
            http2.get(url, function(rsp) {
                angular.forEach(rsp.data, function(rule) {
                    $scope.rules[rule.act].id = rule.id;
                    $scope.rules[rule.act].delta = rule.delta;
                });
            });
        };
        $scope.fetch();
    }]);
})();