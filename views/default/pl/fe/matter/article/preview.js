define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="' + $scope.editing.title + '二维码.png"></a>')[0].click();
        };
        var modifiedData = {};

        $scope.modified = false;
        $scope.submit = function() {
            http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id, modifiedData, function() {
                modifiedData = {};
                $scope.modified = false;
                noticebox.success('完成保存');
            });
        };
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.editing.siteid + '&type=article&id=' + $scope.editing.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        };
        $scope.$watch('editing', function(oArticle) {
            if (oArticle) {
                $scope.previewURL = oArticle.entryUrl + '&preview=Y';
            }
        });
    }]);
});