define(['require', 'enrollService'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'frapontillo.bootstrap-switch', 'ui.tms', 'tmplshop.ui.xxt', 'service.matter', 'service.enroll', 'tinymce.enroll', 'ui.xxt', 'ngAnimate']);
    ngApp.constant('cstApp', {
        notifyMatter: [{
            value: 'tmplmsg',
            title: '模板消息',
            url: '/rest/pl/fe/matter'
        }, {
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
            title: '登记活动',
            url: '/rest/pl/fe/matter'
        }],
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
        }],
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项',
            'require.mission.phase': '请先指定项目的阶段'
        }
    });
    ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', '$uibTooltipProvider', 'srvSiteProvider', 'srvQuickEntryProvider', 'srvEnrollAppProvider', 'srvEnrollRoundProvider', 'srvEnrollPageProvider', 'srvEnrollRecordProvider', function($controllerProvider, $routeProvider, $locationProvider, $compileProvider, $uibTooltipProvider, srvSiteProvider, srvQuickEntryProvider, srvEnrollAppProvider, srvEnrollRoundProvider, srvEnrollPageProvider, srvEnrollRecordProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/pl/fe/matter/enroll/');
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
            controller: $controllerProvider.register,
            directive: $compileProvider.directive
        };
        $routeProvider
            .when('/rest/pl/fe/matter/enroll/main', new RouteParam('main'))
            .when('/rest/pl/fe/matter/enroll/publish', new RouteParam('publish'))
            .when('/rest/pl/fe/matter/enroll/schema', new RouteParam('schema'))
            .when('/rest/pl/fe/matter/enroll/page', new RouteParam('page'))
            .when('/rest/pl/fe/matter/enroll/record', new RouteParam('record'))
            .when('/rest/pl/fe/matter/enroll/editor', new RouteParam('editor'))
            .when('/rest/pl/fe/matter/enroll/recycle', new RouteParam('recycle'))
            .when('/rest/pl/fe/matter/enroll/stat', new RouteParam('stat'))
            .when('/rest/pl/fe/matter/enroll/log', new RouteParam('log'))
            .when('/rest/pl/fe/matter/enroll/coin', new RouteParam('coin'))
            .when('/rest/pl/fe/matter/enroll/prepare', new RouteParam('prepare'))
            .when('/rest/pl/fe/matter/enroll/notice', new RouteParam('notice'))
            .when('/rest/pl/fe/matter/enroll/discuss', new RouteParam('discuss', '/views/default/pl/fe/_module/'))
            .otherwise(new RouteParam('publish'));

        $locationProvider.html5Mode(true);

        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });

        (function() {
            var ls, siteId, appId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]id=([^&]*)/)[1];
            //
            srvSiteProvider.config(siteId);
            srvEnrollAppProvider.config(siteId, appId);
            srvEnrollRoundProvider.config(siteId, appId);
            srvEnrollPageProvider.config(siteId, appId);
            srvEnrollRecordProvider.config(siteId, appId);
            srvQuickEntryProvider.setSiteId(siteId);
        })();
    }]);
    ngApp.controller('ctrlFrame', ['$scope', 'srvSite', 'srvEnrollApp', 'templateShop', '$location', function($scope, srvSite, srvEnrollApp, templateShop, $location) {
        $scope.scenarioNames = {
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报'
        };
        //定义侧边栏数据
        //定义默认状态
        $scope.firstView = ['edit', 'publish', 'data', 'other', 'recycle', 'log'];
        $scope.views = [{
            value: 'edit',
            title: '编辑',
            inferiorShow: false,
            inferior: [{
                value: 'main',
                title: '活动定义'
            }, {
                value: 'schema',
                title: '修改题目'
            }, {
                value: 'page',
                title: '修改页面'
            }]
        }, {
            value: 'publish',
            title: '发布',
            inferiorShow: false,
            inferior: []
        }, {
            value: 'data',
            title: '数据与统计',
            inferiorShow: false,
            inferior: [{
                value: 'record',
                title: '查看数据'
            }, {
                value: 'stat',
                title: '统计报告'
            }]

        }, {
            value: 'recycle',
            title: '回收站',
            inferiorShow: false,
            inferior: []
        }, {
            value: 'log',
            title: '运行日志',
            inferiorShow: false,
            inferior: []
        }, {
            value: 'other',
            title: '其他',
            inferiorShow: false,
            inferior: [{
                    value: 'notice',
                    title: '通知发送记录'
                }, {
                    value: 'coin',
                    title: '积分规则'
                }, {
                    value: 'discuss',
                    title: '活动评论'

                }]
                //},{
        }];
        //侧边栏代码
        $scope.leftSlider = {
            //初始化状态,关闭折叠
            init: function(){
                angular.forEach($scope.views, function(v) {
                    v.inferiorShow = false ;
                });
                //所有状态为false
                $scope.leftState = false ;
                $scope.leftInferior = false ;
            },
            //去一级页面
            goToFirst: function(value){
                this.init();
                //如果二级不为空(用v.inferior.length判断)，打开折叠；否则跳转页面
                angular.forEach($scope.views, function(v) {
                    if(v.value===value){
                        $scope.leftState = v.value;
                        if( v.inferior.length){
                            v.inferiorShow = true;
                        }else{
                            var url = '/rest/pl/fe/matter/enroll/';
                            url += value;
                            $location.path(url);
                        }
                        //跳出循环
                    }
                });
            },
            //去二级页面
            goToSecond: function(value){
                //关闭所有折叠
                this.init();
                angular.forEach($scope.views, function(v) {
                    angular.forEach(v.inferior, function(i) {
                        if(i.value===value){
                            $scope.leftState = v.value;
                            $scope.leftInferior = i.value;
                            v.inferiorShow = true ;
                            //跳转页面
                            var url = '/rest/pl/fe/matter/enroll/';
                            url += value;
                            $location.path(url);
                            //跳出循环
                        }
                    });
                });
            },
            //区分一二级单页，特殊单页处理
            goTo: function(value){
                //如果在一级页面查到 返回值不为-1; 特殊处理-修改数据单页，刷新无状态的bug
                if(value==='editor'){
                    $scope.leftState = 'data';
                    $scope.leftInferior = 'record';
                    angular.forEach($scope.views, function(v) {
                        if(v.value==='data'){
                            v.inferiorShow = true;
                        }
                    })
                }else if($scope.firstView.indexOf(value)!==-1){
                    this.goToFirst(value);
                }else{
                    this.goToSecond(value);
                }
            }
        };
        var subView = location.href.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'enroll' ? 'publish' : subView[1];
        $scope.leftSlider.goTo($scope.subView);
        $scope.update = function(name) {
            srvEnrollApp.update(name);
        };
        $scope.shareAsTemplate = function() {
            templateShop.share($scope.app.siteid, $scope.app).then(function(template) {
                location.href = '/rest/pl/fe/template/enroll?site=' + template.siteid + '&id=' + template.id;
            });
        };

        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(aSns) {
            $scope.sns = aSns;
        });
        srvSite.memberSchemaList().then(function(aMemberSchemas) {
            $scope.memberSchemas = aMemberSchemas;
        });
        srvEnrollApp.get().then(function(app) {
            $scope.app = app;
            app.__schemasOrderConsistent = 'Y'; //页面上登记项显示顺序与定义顺序一致
            // 用户评论
            if (app.can_discuss === 'Y') {
                $scope.discussParams = {
                    title: app.title,
                    threadKey: 'enroll,' + app.id,
                    domain: app.siteid
                };
            }
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
