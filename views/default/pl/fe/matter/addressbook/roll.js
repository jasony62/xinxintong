 ngApp.provider.controller('ImportAddressbookModalInstCtrl', ['$scope', '$uibModalInstance', 'abid', function($scope, $mi, abid) {
     $scope.options = {};
     $scope.options.cleanExistent = 'N';
     $scope.ok = function() {
         $('#formImport').ajaxSubmit({
             url: '/rest/pl/fe/matter/addressbook/import?abid=' + abid + '&cleanExistent=' + $scope.options.cleanExistent,
             type: 'POST',
             success: function(rsp) {
                 if (typeof rsp === 'string')
                     $scope.$root.errmsg = rsp;
                 else
                     $scope.$root.infomsg = rsp.err_msg;
                 $mi.close();
             }
         });
     };
     $scope.cancel = function() {
         $mi.dismiss();
     };
 }]);
 define(['frame'], function(ngApp) {
     ngApp.provider.controller('ctrlRoll', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
         $scope.abbr = '';
         $scope.page = {
             at: 1,
             size: 30
         };
         $scope.doSearch = function() {
             var url = '/rest/pl/fe/matter/addressbook/person?abid=' + $scope.abid + '&page=' + $scope.page.at + '&size=' + $scope.page.size + '&abbr=' + $scope.abbr;
             http2.get(url, function(rsp) {
                 $scope.page.total = rsp.data.amount;
                 $scope.persons = rsp.data.objects;
             });
         };
         $scope.create = function() {
             http2.get('/rest/pl/fe/matter/addressbook/personCreate?abid=' + $scope.abid, function(rsp) {
                 location.href = '/page/pl/fe/matter/addressbook/person?abid=' + $scope.abid + '&id=' + rsp.data.id;
             });
         };
         $scope.edit = function(person) {
             location.href = '/page/pl/fe/matter/addressbook/person?id=' + person.id;
         };
         $scope.keypress = function(event) {
             if (event.keyCode == 13)
                 $scope.doSearch();
         }
         $scope.showImport = function() {
             $uibModal.open({
                 templateUrl: 'modalImportAddressbook.html',
                 controller: 'ImportAddressbookModalInstCtrl',
                 resolve: {
                     abid: function() {
                         return $scope.editing.id;
                     }
                 }
             }).result.then(function() {
                 $scope.doSearch();
             });
         };
         $scope.$watch('abid', function(nv) {
             $scope.doSearch();
         });
     }]);
 });
