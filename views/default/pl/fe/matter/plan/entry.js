define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', '$timeout', 'srvPlanApp', function($scope, $timeout, srvPlanApp) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        srvPlanApp.get().then(function(oApp) {
            var oEntry;
            oEntry = {
                url: oApp.entryUrl,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + oApp.siteid + '&url=' + encodeURIComponent(oApp.entryUrl),
            };
            $scope.entry = oEntry;
        });
    }]);
});