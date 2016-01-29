xxtApp.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
    $scope.rules = {
        'mp.matter.article.read': {
            act: 'mp.matter.article.read',
            desc: '阅读单图文1次',
            delta: 0
        },
        'mp.matter.article.share.T': {
            act: 'mp.matter.article.share.T',
            desc: '分享单图文到朋友圈',
            delta: 0
        },
        'mp.matter.article.share.F': {
            act: 'mp.matter.article.share.F',
            desc: '分享单图文给好友',
            delta: 0
        },
        'mp.matter.article.appraise': {
            act: 'mp.matter.article.appraise',
            desc: '单图文点赞1次',
            delta: 0
        },
        'mp.matter.article.remark': {
            act: 'mp.matter.article.remark',
            desc: '单图文评论1次',
            delta: 0
        }
    };
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
        url = '/rest/mp/coin/save';
        http2.post(url, posted, function(rsp) {
            $scope.$root.infomsg = '保存成功';
            angular.forEach(rsp.data, function(id, act) {
                $scope.rules[act].id = id;
            });
        });
    };
    $scope.fetch = function() {
        var url;
        url = '/rest/mp/coin/get';
        http2.get(url, function(rsp) {
            angular.forEach(rsp.data, function(rule) {
                $scope.rules[rule.act].id = rule.id;
                $scope.rules[rule.act].delta = rule.delta;
            });
        });
    };
    $scope.fetch();
}]);