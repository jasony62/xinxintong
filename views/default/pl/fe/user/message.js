/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function (ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMessage', ['$scope', 'http2', '$uibModal', '$timeout', 'noticebox', function ($scope, http2, $uibModal, $timeout, noticebox) {
        var page, page2;
        $scope.page = page = {
            at: 1,
            size: 30,
            text: '',
            state: '',
            j: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.page2 = page2 = {
            at: 1,
            size: 30,
            j: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.selMatter = [{
                value: 'write',
                content: '编辑'
            },
            {
                value: 'text',
                content: '文本'
            },
            {
                value: 'article',
                content: '单图文'
            },
            {
                value: 'link',
                content: '链接'
            },
            {
                value: 'channel',
                content: '频道'
            }
        ];
        $scope.matterType = $scope.selMatter[0].value; //默认显示编辑项
        //获取消息 包括 用户 和 管理员；用什么区分 用户信息左侧显示，管理员右侧显示
        $scope.doSearch = function () {
            var url = '/rest/pl/fe/user/fans/track?site=' + $scope.siteId + '&openid=' + $scope.sns.openId + $scope.page.j();
            http2.get(url).then(function (rsp) {
                $scope.track = rsp.data.data;
                $scope.page.total = rsp.data.total;
            });
        };
        //问题：公众号信息异步获取；可能得不到，监听
        $scope.$watch('fans', function (fans) {
            if (!fans) {
                return;
            }
            //压缩-插件化
            var data = {},
                obj = {};
            $scope.selSync = [];
            //代码不执行 定义状态
            if (!(fans.wx || fans.qy)) {
                $scope.page.state = 1;
                return;
            } else {
                $scope.page.state = 2
            }
            fans.wx && (data.wx = fans.wx);
            fans.qy && (data.qy = fans.qy);
            angular.forEach(data, function (d, key) {
                if (key === 'wx') {
                    obj.title = '微信公众号';
                    obj.content = '微信公众号信息';
                    obj.src = 'wx';
                } else if (key === 'qy') {
                    obj.title = '微信企业号';
                    obj.content = '微信企业号信息';
                    obj.src = 'qy';
                }
                obj.openId = d.openid;
                $scope.selSync.push(obj);
                obj = {};
            });
            //默认显示公众号
            $scope.selSync && $scope.selSync.length && ($scope.sns = $scope.selSync[0]); //默认显示微信公众号
            $scope.doSearch();
        });
        $scope.send = function () {
            var data;
            if ($scope.matterType === 'write') {
                data = {
                    //直接用$scope.text获取不到信息 原因暂时不明
                    text: $scope.page.text
                };
            } else {
                data = {
                    id: $scope.selectedMatter.id,
                    type: $scope.matterType,
                    title: $scope.selectedMatter.title || $scope.selectedMatter.content
                };
            }
            //发送接口？
            http2.post('/rest/pl/fe/user/send/custom?site=' + $scope.siteId + '&openid=' + $scope.sns.openId + '&src=' + $scope.sns.src, data).then(function (rsp) {
                //初始化分页
                $scope.page.at = 1;
                $scope.doSearch();
                noticebox.success('完成');
                $scope.page.text = '';
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
            http2.get(url).then(function (rsp) {
                $scope.matters = rsp.data.docs;
                $scope.page2.total = rsp.data.total;
            });
        };
        //选择资料
        $scope.selectMatter = function (matter) {
            $scope.selectedMatter = matter;
        };
        //切换公众号
        $scope.checkoutSns = function (sns) {
            //初始化分页
            // 双向绑定失败js得不到$scope.sns 因此传入sns
            $scope.sns = sns;
            $scope.page.at = 1;
            $scope.page2.at = 1;
            $scope.doSearch();
        }
    }]);
});