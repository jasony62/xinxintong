define(['require'], function() {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'service.matter', 'service.article']);
    ngApp.constant('cstApp', {
        innerlink: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            scenario: 'registration',
            title: '报名',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            scenario: 'voting',
            title: '投票',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'signin',
            title: '签到',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'wall',
            title: '讨论组',
            url: '/rest/pl/fe/matter'
        }],
    });
    ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvSiteProvider', 'srvAppProvider', function($routeProvider, $locationProvider, $controllerProvider, srvSiteProvider, srvAppProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/article/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            this.resolve = {
                load: function($q) {
                    var defer = $q.defer();
                    require([baseURL + name + '.js'], function() {

                        defer.resolve();
                    });
                    return defer.promise;
                }
            };
        };
        ngApp.provider = {
            controller: $controllerProvider.register
        };
        $routeProvider.when('/rest/pl/fe/matter/article/log', new RouteParam('log'))
            .when('/rest/pl/fe/matter/article/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/article/discuss', new RouteParam('discuss', '/views/default/pl/fe/_module/'))
            .otherwise(new RouteParam('main'));

        $locationProvider.html5Mode(true);
        //设置服务参数
        (function() {
            var ls, siteId, articleId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            articleId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvAppProvider.setSiteId(siteId);
            srvAppProvider.setAppId(articleId);
        })();
    }]);
    ngApp.controller('ctrlArticle', ['$scope', 'srvSite', 'srvApp', '$http', function($scope, srvSite, srvApp, http) {
        $scope.viewNames = {
            'main': '发布预览',
            'coin': '积分规则',
            'log': '运行日志',
        };
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView[1] === 'article' ? 'main' : subView[1];
        });
        $scope.update = function(names) {
            return srvApp.update(names);
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvApp.get().then(function(editing) {
            $scope.editing = editing;
            !editing.attachments && (editing.attachments = []);
            $scope.entry = {
                url: editing.entryUrl,
                qrcode: '/rest/site/fe/matter/article/qrcode?site=' + $scope.editing.siteid + '&url=' + encodeURIComponent(editing.entryUrl),
            };
            // 用户评论
            if (editing.can_discuss === 'Y') {
                $scope.discussParams = {
                    title: editing.title,
                    threadKey: 'article,' + editing.id,
                    domain: editing.siteid
                };
            }
        });
        window.onbeforeunload = function(e) {
            if( !$scope.editing.pic && !$scope.editing.thumbnail){
                var canvas, context, img, url,
                    H = 96,
                    W = 96;
//    canvas = document.getElementById('canvas');
//    创建一个canvas 900像素 * 500像素
                canvas = document.createElement('canvas');
                canvas.width = W;
                canvas.height = H;
                context = canvas.getContext('2d');
                context.fillStyle = '#50555B';
                //context.fillRect(0,0,500,500);
                //设置绘制颜色
                //设置绘制线性?
                context.fillStyle = "#50555B";
                context.strokeStyle = "#fff";
                //填充一个矩形
                context.beginPath();//表示开始创建路径
                context.rect(0,0,W,H);//设置矩形区域
                context.closePath();//表示结束创建路径
                context.fill();//绘制图形
                //绘制一个圆
                context.lineWidth = '2';
                context.beginPath();
                context.arc(W/2,H/2,(W-10)/2,0,Math.PI*2);
                context.closePath();
                context.stroke();
                ////填充一个圆
                context.fillStyle = "#fff";
                context.beginPath();
                context.arc(W/2,H/2,(W-10-8)/2,0,Math.PI*2);
                context.closePath();
                context.fill();
                //
                context.fillStyle = "#CE2157";
                context.font = "bold 40px 微软雅黑";
                context.beginPath();
                context.stroke();
                context.textAlign = "center";
                ////1.填充一个灰色矩形，
                ////2.虚线圆
                ////3.填充白色圆
                ////4.中间一个字
                ////获取字符串第一个字
                context.fillText($scope.editing.title.slice(0,1),W/2,(H+30)/2);
                //提交数据
                $scope.editing.pic = canvas.toDataURL('img/png');
                url = '/rest/pl/fe/matter/article/update?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id;
                http2.post(url,{'pic':$scope.editing.pic});
            }
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
