xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/contribute/coin', {
        templateUrl: '/views/default/mp/app/contribute/coin.html?_=1',
        controller: 'ctrlCoin'
    }).otherwise({
        templateUrl: '/views/default/mp/app/contribute/setting.html?_=1',
        controller: 'ctrlSetting'
    });
}]);
xxtApp.controller('ctrlContribute', ['$location', '$scope', 'http2', function($location, $scope, http2) {
    var aid = $location.search().aid;
    $scope.aid = aid;
    $scope.taskCodeEntryUrl = 'http://' + location.host + '/rest/q';
    $scope.back = function() {
        history.back();
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        http2.get('/rest/mp/app/contribute/get?id=' + aid, function(rsp) {
            var app, entryUrl, ch, mapChannels = {};
            app = rsp.data;
            entryUrl = 'http://' + location.hostname + '/rest/app/contribute';
            entryUrl += '?mpid=' + $scope.mpaccount.mpid;
            entryUrl += '&entry=contribute,' + app.id;
            $scope.entryUrl = entryUrl;
            $scope.editing = app;
            app.canSetInitiator = 'Y';
            app.canSetReviewer = 'Y';
            app.canSetTypesetter = 'Y';
            app.params = app.params ? JSON.parse(app.params) : {};
            app.subChannels = [];
            angular.forEach(app.channels, function(ch) {
                mapChannels[ch.id] = ch;
            });
            if (app.params.subChannels && app.params.subChannels.length) {
                var i, j, cid;
                angular.forEach(app.params.subChannels, function(cid) {
                    app.subChannels.push(mapChannels[cid]);
                });
            }
            $scope.editing = app;
        });
    });
}]);
xxtApp.controller('ctrlSetting', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'setting';
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/contribute/update?id=' + $scope.editing.id, nv);
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                $scope.update('pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.$on('sub-channel.xxt.combox.done', function(event, data) {
        var editing;
        editing = $scope.editing;
        editing.params.subChannels === undefined && (editing.params.subChannels = []);
        angular.forEach(data, function(c) {
            editing.subChannels.push({
                id: c.id,
                title: c.title
            });
            editing.params.subChannels.push(c.id);
        });
        $scope.update('params');
    });
    $scope.$on('sub-channel.xxt.combox.del', function(event, ch) {
        var i, editing = $scope.editing;
        i = editing.subChannels.indexOf(ch);
        editing.subChannels.splice(i, 1);
        i = editing.params.subChannels.indexOf(ch.id);
        editing.params.subChannels.splice(i, 1);
        $scope.update('params');
    });
    http2.get('/rest/mp/matter/channel/get?cascade=N', function(rsp) {
        $scope.channels = rsp.data;
    });
}]);
xxtApp.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
    var prefix = 'app.contribute,' + $scope.aid,
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
        url = '/rest/mp/app/contribute/coin/save';
        http2.post(url, posted, function(rsp) {
            $scope.$root.infomsg = '保存成功';
            angular.forEach(rsp.data, function(id, act) {
                $scope.rules[act].id = id;
            });
        });
    };
    $scope.fetch = function() {
        var url;
        url = '/rest/mp/app/contribute/coin/get?aid=' + $scope.aid;
        http2.get(url, function(rsp) {
            angular.forEach(rsp.data, function(rule) {
                $scope.rules[rule.act].id = rule.id;
                $scope.rules[rule.act].delta = rule.delta;
            });
        });
    };
    $scope.fetch();
}]);