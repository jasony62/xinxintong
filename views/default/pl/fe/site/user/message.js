/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function (ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMessage', ['$scope', 'http2', '$uibModal', '$timeout', function ($scope, http2, $uibModal, $timeout) {
        var page,page2,
            baseURL = '/rest/pl/fe/site/user/';
        $scope.page = page = {
            at: 1,
            size: 10,
            j: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.page2 = page2 = {
            at: 1,
            size: 2,
            j: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.sel = [
            {value: 'write', content: '编辑'},
            {value: 'text', content: '文本'},
            {value: 'article', content: '单图文'},
            {value: 'news', content: '多图文'},
            {value: 'link', content: '链接'},
            {value: 'channel', content: '频道'}
        ];
        $scope.matterType = 'write';
        //获取消息 包括 用户 和 管理员；用什么区分 用户信息左侧显示，管理员右侧显示
        $scope.doSearch = function () {
            var url = '/rest/pl/fe/site/user/fans/track?site=' + $scope.siteId + '&openid=' + $scope.openid + $scope.page.j();
            http2.get(url, function(rsp) {
                //$scope.track = rsp.data;
                //$scope.page.total = 20;
                //$scope.track = [
                //    {
                //        "creater": "5715c9edc7738",
                //        "create_at": "1490771516",
                //        "content": "aaaaa",
                //        "matter_id": "",
                //        "matter_type": ""
                //    },
                //    {"create_at": "1490232005", "content": "huininghui"}
                //];
            });
        };
        $scope.doSearch();
        $scope.send = function(openId) {
            var data;
            if ($scope.matterType === 'write') {
                data = {
                    text: $scope.text
                };
            } else {
                data = {
                    id: $scope.selectedMatter.id,
                    type: $scope.matterType,
                    title: $scope.selectedMatter.title || $scope.selectedMatter.content
                };
            }
            //发送接口？
            http2.post('/rest/pl/fe/site/user/send/custom?site=' + $scope.siteId + '&openid=' + openId, data, function(rsp) {
                $scope.doSearch();
            });
        };
        //获取资料
        $scope.fetchMatter = function(page) {
            if ($scope.matterType === 'write') {
                $scope.matters = null;
                return;
            }
            $scope.selectedMatter = null;
            var url, params = {};
            url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId;
            url += $scope.page2.j();
            //$scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
            http2.get(url, function(rsp) {
                //除了单图文都没有total 都没有做分页
                if (/article/.test($scope.matterType)) {
                    $scope.matters = rsp.data.articles;
                    $scope.page2.total = rsp.data.total;
                } else {
                    $scope.matters = rsp.data;
                }
            });
        };
        //选择资料
        $scope.selectMatter = function(matter) {
            $scope.selectedMatter = matter;
        };
    }]);
});