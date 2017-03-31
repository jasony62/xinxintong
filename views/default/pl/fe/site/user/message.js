/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function (ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMessage', ['$scope', 'http2', '$uibModal', '$timeout', function ($scope, http2, $uibModal, $timeout) {
        var page, page2;
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
        $scope.selMatter = [
            {value: 'write', content: '编辑'},
            {value: 'text', content: '文本'},
            {value: 'article', content: '单图文'},
            {value: 'news', content: '多图文'},
            {value: 'link', content: '链接'},
            {value: 'channel', content: '频道'}
        ];
        $scope.matterType = $scope.selMatter[0].value;//默认显示编辑项
        //获取消息 包括 用户 和 管理员；用什么区分 用户信息左侧显示，管理员右侧显示
        $scope.doSearch = function (syncOpenId) {
            $scope.openId = syncOpenId ? syncOpenId : $scope.syncOpenId;
            //初始化分页
            $scope.page.at = 1;
            $scope.page2.at = 1;
            //syncOpenId && ($scope.syncOpenId = syncOpenId);
            var url = '/rest/pl/fe/site/user/fans/track?site=' + $scope.siteId + '&openid=' + $scope.openId + $scope.page.j();
            http2.get(url, function (rsp) {
                $scope.track = rsp.data.data;
                //$scope.page.total = rsp.data.total;
                $scope.page.total = rsp.data.total;
            });
        };
        //问题：公众号信息异步获取；可能得不到，重新获取
        http2.get('/rest/pl/fe/site/user/fans/getsnsinfo?site=' + $scope.siteId + '&uid=' + $scope.userId, function (rsp) {
            //var fans = rsp.data;
            //fans.wx && ($scope.wx = fans.wx);
            //fans.qy && ($scope.qy = fans.qy);
            //fans.yx && ($scope.yx = fans.yx);
            //$scope.wx && ($scope.selSync[length] = {value: $scope.wx.openid, title: '微信公众号', content: '微信公众号信息'});
            //$scope.qy && ($scope.selSync[length] = {value: $scope.qy.openid, title: '微信企业号', content: '微信企业号信息'});
            //$scope.yx && ($scope.selSync[length] = {value: $scope.yx.openid, title: '易信公众号', content: '易信公众号信息'});
            //压缩-插件化
            var data = {},
                fans = rsp.data,
                obj = {};
            $scope.selSync = [];
            //代码不执行
            if (!(fans.wx || fans.qy || fans.yx))  {return;}
            fans.wx && (data.wx = fans.wx);
            fans.qy && (data.qy = fans.qy);
            fans.yx && (data.yx = fans.yx);
            angular.forEach(data, function (d, key) {
                if (key === 'wx') {
                    obj.title = '微信公众号';
                    obj.content = '微信公众号信息' ;
                }else if(key === 'qy'){
                    obj.title = '微信企业号';
                    obj.content = '微信企业号信息' ;
                }else if(key === 'yx'){
                    obj.title = '易信公众号';
                    obj.content = '易信公众号信息' ;
                }
                obj.value = d.openid ;
                $scope.selSync[length] = obj;
                obj = {};
            });
            $scope.selSync && $scope.selSync.length && ($scope.syncOpenId = $scope.selSync[0].value);//默认显示微信公众号
            $scope.doSearch();
        });
        $scope.send = function () {
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
            http2.post('/rest/pl/fe/site/user/send/custom?site=' + $scope.siteId + '&openid=' + $scope.openId, data, function (rsp) {
                $scope.doSearch();
            });
        };
        //获取资料
        $scope.fetchMatter = function (matterType) {
            $scope.matterType = matterType ? matterType : $scope.matterType;
            if ($scope.matterType === 'write') {
                $scope.matters = null;
                return;
            }
            $scope.selectedMatter = null;
            var url, params = {};
            url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId;
            url += $scope.page2.j();
            //$scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
            http2.get(url, function (rsp) {
                //除了单图文都没有total 都没有做分页
                if (/article/.test(matterType)) {
                    $scope.matters = rsp.data.articles;
                    $scope.page2.total = rsp.data.total;
                } else {
                    $scope.matters = rsp.data;
                }
            });
        };
        //选择资料
        $scope.selectMatter = function (matter) {
            $scope.selectedMatter = matter;
        };
    }]);
});