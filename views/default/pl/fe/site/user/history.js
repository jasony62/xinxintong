/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function (ngApp) {

    'use strict';
    ngApp.provider.controller('ctrlHistory', ['$scope', 'http2', function ($scope, http2) {
        var page,
            baseURL = '/rest/pl/fe/site/user/';
        $scope.historys = {
            enroll: {
                title: '活动记录',
                content: [],
                total:0
            },
            read: {
                title: '阅读记录',
                content: [],
                total:0

            },
            favor: {
                title: '收藏记录',
                content: [],
                total:0
            }
        };
        $scope.page = page = {
            at: 1,
            size: 30,
            type : '活动记录',
            total : $scope.historys.enroll.total,
            j: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        //获取活动记录
        $scope.enrollList = function () {
            http2.get('/rest/site/fe/user/history/appList?site=' + $scope.siteId + '&uid=' + $scope.userId + page.j(), function (rsp) {
                $scope.historys.enroll.content = rsp.data.apps;
                rsp.data.total ? $scope.historys.enroll.total = rsp.data.total : 0;
            });
        };
        //获取阅读记录
        $scope.readList = function () {
            http2.get('/rest/pl/fe/site/user/readList?site=' + $scope.siteId + '&uid=' + $scope.userId + page.j(), function (rsp) {
                $scope.historys.read.content = rsp.data.matters;
                rsp.data.total ? $scope.historys.read.total = rsp.data.total : 0;
            });
        };
        //获取收藏记录
        $scope.favorList = function () {
            http2.get('/rest/site/fe/user/favor/list?site=' + $scope.siteId + '&uid=' + $scope.userId + page.j(), function (rsp) {
                $scope.historys.favor.content = rsp.data.matters;
                rsp.data.total ? $scope.historys.favor.total = rsp.data.total : 0;
            });
        };
        $scope.enrollList();
        $scope.readList();
        $scope.favorList();
        //分页
        $scope.doSearch = function(){
            $scope.page.type === '活动记录' ? $scope.enrollList() : $scope.page.type === '阅读记录'? $scope.readList(): $scope.favorList();
        };
        //切换tal页签 初始化分页，修改type,修改page
        $scope.init = function (type) {
            if(type===$scope.page.type){return;}
            $scope.page.at = 1;
            $scope.page.type = type;
            $scope.page.total = type === '活动记录' ? $scope.historys.enroll.total : type === '阅读记录'? $scope.historys.read.total: $scope.historys.favor.total;
        };
        //管理员打开活动
        //$scope.openApp = function(app){
        //	location.href = '/rest/pl/fe/matter/' + app.matter_type + '?id=' + app.matter_id + '&site=' + $scope.siteId;
        //}
    }])

});
