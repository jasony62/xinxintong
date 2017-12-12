var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt', 'channel.fe.pl', 'service.matter']);
ngApp.config(['$routeProvider', '$locationProvider', 'srvSiteProvider', 'srvTagProvider', function($routeProvider, $locationProvider, srvSiteProvider, srvTagProvider) {
    $routeProvider.when('/rest/pl/fe/matter/custom', {
        templateUrl: '/views/default/pl/fe/matter/custom/setting.html?_=2',
        controller: 'ctrlSetting',
    }).otherwise({
        templateUrl: '/views/default/pl/fe/matter/custom/setting.html?_=2',
        controller: 'ctrlSetting'
    });
    $locationProvider.html5Mode(true);
    //设置服务参数
    (function() {
        var siteId;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        //
        srvSiteProvider.config(siteId);
        srvTagProvider.config(siteId);
    })();
}]);
ngApp.controller('ctrlCustom', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
    var ls = $location.search();
    $scope.id = ls.id;
    $scope.siteId = ls.site;
    srvSite.tagList().then(function(oTag) {
        $scope.oTag = oTag;
    });
    srvSite.tagList('C').then(function(oTag) {
        $scope.oTagC = oTag;
    });
    http2.get('/rest/pl/fe/matter/custom/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
        var url;
        $scope.editing = rsp.data;
        if($scope.editing.matter_cont_tag !== ''){
            $scope.editing.matter_cont_tag.forEach(function(cTag,index){
                $scope.oTagC.forEach(function(oTag){
                    if(oTag.id === cTag){
                        $scope.editing.matter_cont_tag[index] = oTag;
                    }
                });
            });
        }
        if($scope.editing.matter_mg_tag !== ''){
            $scope.editing.matter_mg_tag.forEach(function(cTag,index){
                $scope.oTag.forEach(function(oTag){
                    if(oTag.id === cTag){
                        $scope.editing.matter_mg_tag[index] = oTag;
                    }
                });
            });
        }
        url = 'http://' + location.host + '/rest/site/fe/matter?site=' + ls.site + '&id=' + ls.id + '&type=custom';
        $scope.entry = {
            url: url,
            qrcode: '/rest/site/fe/matter/article/qrcode?site=' + ls.site + '&url=' + encodeURIComponent(url),
        };
    });
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', 'templateShop', '$uibModal', 'srvTag', function($scope, http2, mediagallery, templateShop, $uibModal, srvTag) {
    var modifiedData = {};
    $scope.modified = false;
    $scope.back = function() {
        history.back();
    };
    window.onbeforeunload = function(e) {
        var message;
        if ($scope.modified) {
            message = '修改还没有保存，是否要离开当前页面？',
                e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.submit = function() {
        http2.post('/rest/pl/fe/matter/custom/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
            modifiedData = {};
            $scope.modified = false;
        });
    };
    $scope.update = function(name) {
        $scope.modified = true;
        modifiedData[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
    };
    $scope.copy = function() {
        http2.get('/rest/pl/fe/matter/custom/copy?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
            location.href = '/rest/pl/fe/matter/custom?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.remove = function() {
        http2.get('/rest/pl/fe/matter/custom/remove?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
            location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
        });
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                $scope.update('pic');
            }
        };
        mediagallery.open($scope.siteId, options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.gotoCode = function() {
        var name = $scope.editing.body_page_name;
        if (name && name.length) {
            window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name);
        } else {
            http2.get('/rest/pl/fe/code/create?site=' + $scope.siteId, function(rsp) {
                var nv = {
                    'page_id': rsp.data.id,
                    'body_page_name': rsp.data.name
                };
                http2.post('/rest/pl/fe/matter/custom/update?site=' + $scope.siteId + '&id=' + $scope.id, nv, function() {
                    $scope.editing.page_id = rsp.data.id;
                    $scope.editing.body_page_name = rsp.data.name;
                    window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name);
                });
            });
        }
    };
    $scope.selectTemplate = function() {
        templateShop.choose('custom').then(function(data) {
            http2.get('/rest/pl/fe/matter/custom/pageByTemplate?id=' + $scope.editing.id + '&template=' + data.id, function(rsp) {
                $scope.editing.page_id = rsp.data.id;
                $scope.editing.body_page_name = rsp.data.name;
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + $scope.editing.body_page_name;
            });
        });
    };
    $scope.saveAsTemplate = function() {
        var matter, editing;
        editing = $scope.editing;
        matter = {
            id: editing.id,
            type: 'custom',
            title: editing.title,
            pic: editing.pic,
            summary: editing.summary
        };
        templateShop.share($scope.siteId, matter).then(function() {
            $scope.$root.infomsg = '成功';
        });
    };
    $scope.tagMatter = function(subType){
        var oTags;
        if (subType === 'C') {
            oTags = $scope.oTagC;
        } else {
            oTags = $scope.oTag;
        }
        srvTag._tagMatter($scope.editing, oTags, subType);
    };
    (function() {
        new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
    })();
    $scope.downloadQrcode = function(url) {
        $('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
    };
}]);
