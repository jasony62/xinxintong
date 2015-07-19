xxtApp.controller('parentmpCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.matterType = 'text';
    $scope.page = { at: 1, size: 30 };
    $scope.selectedMps = [];
    $scope.selectMp = function (mp) {
        if (mp.checked === 'Y') {
            $scope.selectedMps.push(mp);
        } else {
            var i = $scope.selectedMps.indexOf(mp);
            $scope.selectedMps.splice(i, 1);
        }
    };
    $scope.selectAllMps = function () {
        $scope.selectedMps = [];
        for (var i = 0; i < $scope.childmps.length; i++) {
            $scope.childmps[i].checked = 'Y';
            $scope.selectedMps.push($scope.childmps[i]);
        }
    };
    $scope.selectMatter = function (matter) {
        $scope.selectedMatter = matter;
    };
    $scope.fetchMatter = function (page) {
        $scope.selectedMatter = null;
        var url = '/rest/mp/matter/' + $scope.matterType;
        !page && (page = $scope.page.at);
        url += '/get?page=' + page + '&size=' + $scope.page.size;
        if ($scope.fromParent && $scope.fromParent === 'Y')
            url += '&src=p';
        http2.get(url, function (rsp) {
            if ('article' === $scope.matterType) {
                $scope.matters = rsp.data[0];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
            } else
                $scope.matters = rsp.data;
        });
    };
    $scope.send = function (evt) {
        var i = 0, mps = [];
        for (i; i < $scope.selectedMps.length; i++) {
            mps.push($scope.selectedMps[i].mpid);
        }
        var data = {
            id: $scope.selectedMatter.id,
            type: $scope.matterType,
            mps: mps,
        };
        http2.post('/rest/mp/send/mass2mps', data, function (rsp) {
            var result;
            for (var mpid in rsp.data) {
                result = rsp.data[mpid];
                $scope.mapOfMps[mpid].logs.splice(0, 0, { result: result });
            }
        });
    };
    $scope.fetchMatter();
    http2.get('/rest/mp/mpaccount/childmps', function (rsp) {
        $scope.childmps = rsp.data;
        http2.get('/rest/mp/analyze/massmsg', function (rsp) {
            var i, l, mp, map = {}, log;
            for (i = 0, l = $scope.childmps.length; i < l; i++) {
                mp = $scope.childmps[i];
                mp.logs = [];
                map[mp.mpid] = mp;
            }
            for (i = 0, l = rsp.data.length; i < l; i++) {
                log = rsp.data[i];
                map[log.mpid].logs.push(log);
            }
            $scope.mapOfMps = map;
        });
    });
}]);