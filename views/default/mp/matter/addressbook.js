xxtApp.controller('abCtrl',['$scope','http2',function($scope,http2){
    var getAddressbooks = function() {
        http2.get('/rest/mp/matter/addressbook', function(rsp) {
            $scope.addressbooks = rsp.data;
        });
    };
    $scope.create = function() {
        http2.get('/rest/mp/matter/addressbook/create', function(rsp) {
            location.href = '/page/mp/matter/ab/?id='+rsp.data;
        });
    };
    $scope.edit = function(addressbook) {
        location.href = '/page/mp/matter/ab?id='+addressbook.id;
    };
    $scope.remove = function(event, addressbook, index){
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/matter/addressbook/remove?id='+addressbook.id, function(rsp) {
            $scope.addressbooks.splice(index, 1);
        });
    };
    $scope.doSearch = function() {
        getAddressbooks();
    };
    $scope.doSearch();
}]);
