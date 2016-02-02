xxtApp.controller('TextCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.fromParent = 'N';
    $scope.create = function() {
        var obj = {
            content: '新文本素材',
        };
        http2.post('/rest/mp/matter/text/create', obj, function(rsp) {
            $scope.texts.splice(0, 0, rsp.data);
            $scope.selectOne(0);
        });
    };
    $scope.deleteOne = function(event) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/matter/text/delete?id=' + $scope.editing.id, function(rsp) {
            $scope.texts.splice($scope.selectedIndex, 1);
            if ($scope.texts.length == 0)
                alert('empty');
            else if ($scope.selectedIndex == $scope.texts.length)
                $scope.selectOne($scope.selectedIndex - 1);
            else
                $scope.selectOne($scope.selectedIndex);
        });
    };
    $scope.selectOne = function(index) {
        $scope.selectedIndex = index;
        $scope.editing = $scope.texts[index];
    };
    $scope.update = function(prop) {
        var p = {};
        p[prop] = $scope.editing[prop];
        http2.post('/rest/mp/matter/text/update?id=' + $scope.editing.id, p);
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/text/list',
            params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.texts = rsp.data;
            if ($scope.texts.length > 0)
                $scope.selectOne(0);
        });
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
    });
    http2.get('/rest/mp/feature/get?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    $scope.doSearch();
}]).
filter("truncate", function() {
    return function(text, length) {
        if (text) {
            var ellipsis = text.length > length ? "..." : "";
            return text.slice(0, length) + ellipsis;
        };
        return text;
    }
});