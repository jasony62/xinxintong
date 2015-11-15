(function() {
    xxtApp.register.controller('resultCtrl', ['$rootScope', '$scope', 'http2', function($rootScope, $scope, http2) {
        $scope.$parent.subView = 'result';
        var doSearch = function(page) {
            !page && (page = $scope.page.current);
            var url = '/rest/mp/app/lottery/log/list';
            url += '?lid=' + $scope.lid + '&page=' + page + '&size=' + $scope.page.size;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            if ($scope.byAward && $scope.byAward.length > 0)
                url += '&award=' + $scope.byAward;
            if ($scope.associatedAct)
                url += '&assocAct=' + $scope.associatedAct.aid;
            http2.get(url, function(rsp) {
                $scope.result = rsp.data.result;
                $scope.page.total = rsp.data.total;
                //rsp.data[2] && ($scope.assocDef = rsp.data[2]);
            });
        };
        var doStat = function() {
            http2.get('/rest/mp/app/lottery/stat?lid=' + $scope.lid, function(rsp) {
                $scope.stat = rsp.data;
            });
        };
        $scope.byAward = '';
        $scope.page = {
            current: 1,
            size: 30
        };
        var current, startAt, endAt;
        current = new Date();
        startAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
                return d.getTime();
            }
        };
        endAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
                return d.getTime();
            }
        };
        $scope.startAt = startAt.getTime() / 1000;
        $scope.endAt = endAt.getTime() / 1000;
        $scope.$on('xxt.tms-datepicker.change', function(evt, data) {
            $scope[data.state] = data.value;
            doSearch(1);
        });
        $scope.doSearch = function(page) {
            page ? doSearch(page) : doSearch();
        };
        $scope.viewUser = function(fan) {
            location.href = '/rest/mp/user?openid=' + fan.openid;
        };
        $scope.refresh = function() {
            doStat();
            doSearch();
        };
        $scope.removeRoll = function(r) {
            var vcode;
            vcode = prompt('是否要删除当前用户的所有抽奖记录？，若是，请输入活动名称。');
            if (vcode === $scope.lottery.title) {
                var url = '/rest/mp/app/lottery/removeRoll?lid=' + $scope.lid;
                if (r.openid && r.openid.length > 0)
                    url += '&openid=' + r.openid;
                else
                    url += '&mid=' + r.mid;
                http2.get(url, function(rsp) {
                    $scope.refresh();
                });
            }
        };
        $scope.clean = function() {
            var vcode;
            vcode = prompt('是否要重新设置奖项数量，并删除所有抽奖记录？，若是，请输入活动名称。');
            if (vcode === $scope.lottery.title) {
                http2.get('/rest/mp/app/lottery/clean?lid=' + $scope.lid, function(rsp) {
                    $scope.refresh();
                });
            }
        };
        $scope.addChance = function() {
            var vcode;
            vcode = prompt('是否要给未中奖用户增加1次抽奖机会？，若是，请输入活动名称。');
            if (vcode === $scope.lottery.title) {
                http2.get('/rest/mp/app/lottery/addChance?lid=' + $scope.lid, function(rsp) {
                    $rootScope.infomsg = rsp.data;
                });
            }
        };
        $scope.refresh();
    }]);
})();