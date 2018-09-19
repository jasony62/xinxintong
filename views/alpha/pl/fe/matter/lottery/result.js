(function() {
    ngApp.provider.controller('ctrlResult', ['$rootScope', '$scope', 'http2', function($rootScope, $scope, http2) {
        var doSearch = function(page) {
            !page && (page = $scope.page.current);
            var url = '/rest/pl/fe/matter/lottery/result/list';
            url += '?lid=' + $scope.id + '&page=' + page + '&size=' + $scope.page.size;
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
            http2.get('/rest/pl/fe/matter/lottery/stat?lid=' + $scope.id, function(rsp) {
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
            //location.href = '/rest/mp/user?openid=' + fan.openid;
        };
        $scope.refresh = function() {
            doStat();
            doSearch();
        };
        $scope.removeRoll = function(r) {
            var vcode;
            vcode = prompt('是否要删除当前用户的所有抽奖记录？，若是，请输入活动名称。');
            if (vcode === $scope.app.title) {
                var url = '/rest/pl/fe/matter/lottery/removeRoll?lid=' + $scope.id;
                url += '&userid=' + r.userid;
                http2.get(url, function(rsp) {
                    $scope.refresh();
                });
            }
        };
        $scope.clean = function() {
            var vcode;
            vcode = prompt('是否要重新设置奖项数量，并删除所有抽奖记录？，若是，请输入活动名称。');
            if (vcode === $scope.app.title) {
                http2.get('/rest/pl/fe/matter/lottery/clean?lid=' + $scope.id, function(rsp) {
                    $scope.refresh();
                });
            }
        };
        $scope.addChance = function() {
            var vcode;
            vcode = prompt('是否要给未中奖用户增加1次抽奖机会？，若是，请输入活动名称。');
            if (vcode === $scope.app.title) {
                http2.get('/rest/pl/fe/matter/lottery/addChance?lid=' + $scope.id, function(rsp) {
                    $rootScope.infomsg = rsp.data;
                });
            }
        };
        $scope.refresh();
    }]);
})();