 define(['frame'], function(ngApp) {
     ngApp.provider.controller('ctrlRoll', ['$scope', '$uibModal', 'http2', '$location',function($scope, $uibModal, http2,$location) {
         var ls = $location.search();

         $scope.id = ls.id;
         $scope.siteId = ls.site;
         $scope.modified = false;

         $scope.abbr = '';
         $scope.page = {
             at: 1,
             size: 30
         };
         $scope.doSearch = function() {
             var url = '/rest/pl/fe/matter/addressbook/person?abid=' + $scope.id + '&page=' + $scope.page.at + '&size=' + $scope.page.size + '&abbr=' + $scope.abbr;
             http2.get(url, function(rsp) {
                 $scope.page.total = rsp.data.amount;
                 $scope.persons = rsp.data.objects;
             });
         };
         $scope.create = function() {
             http2.get('/rest/pl/fe/matter/addressbook/personCreate?abid=' + $scope.id, function(rsp) {
                 location.href = '/rest/pl/fe/matter/addressbook/personnel?id=' + $scope.id + '&id=' + rsp.data.id;
                 /*location.href = '/rest/pl/fe/matter/addressbook/person?id=' + rsp.data.id + '&site=' + $scope.siteId;*/
             });
         };
         $scope.edit = function(person) {
             location.href = '/rest/pl/fe/matter/addressbook/personnel?id=' + person.id;
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
         $scope.$watch('abid', function(id) {
             $scope.doSearch();
         });
     }]);
     ngApp.provider.controller('ImportAddressbookModalInstCtrl', ['$scope', '$uibModalInstance', 'abid', '$location',function($scope, $mi, abid,$location) {
         var ls = $location.search();

         $scope.id = ls.id;
         $scope.siteId = ls.site;
         $scope.modified = false;

         $scope.options = {};
         $scope.options.cleanExistent = 'N';
         $scope.ok = function() {
             $('#formImport').ajaxSubmit({
                 url: '/rest/pl/fe/matter/addressbook/import?abid=' + $scope.id + '&cleanExistent=' + $scope.options.cleanExistent,
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
 });
