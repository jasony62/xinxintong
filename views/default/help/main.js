var opts = {
    container: 'epiceditor',
    textarea: null,
    basePath: '/views/default/help/epiceditor',
    clientSideStorage: true,
    localStorageName: 'epiceditor',
    useNativeFullscreen: true,
    parser: marked,
    file: {
        name: 'epiceditor',
        defaultContent: '',
        autoSave: 100
    },
    theme: {
        base: '/themes/base/epiceditor.css',
        preview: '/themes/preview/github.css',
        editor: '/themes/editor/epic-light.css'
    },
    button: false,
    focusOnLoad: false,
    shortcut: {
        modifier: 18,
        fullscreen: 70,
        preview: 80
    },
    string: {
        togglePreview: 'Toggle Preview Mode',
        toggleEdit: 'Toggle Edit Mode',
        toggleFullscreen: 'Enter Fullscreen'
    },
    autogrow: false
};
var editor = new EpicEditor(opts).load();
editor.preview();

tmsApp=angular.module('tmsApp',['ui.tms']);
tmsApp.controller('helpCtrl',['$scope','$http',function($scope,$http){
    $scope.updateDoc = function(name) {
        var nv = {},
        url = '/rest/help/updateDoc?id='+$scope.editing.id;
        if (name === 'content')
            $scope.editing.content = editor.exportFile($scope.editing.id);
        nv[name] = $scope.editing[name];
        $http.post(url, nv).success(function(rsp){});
    };
    $scope.addDoc = function() {
        var url = '/rest/help/addDoc';
        $http.get(url).success(function(rsp){
            $scope.docs.push(rsp.data);
            $scope.editDoc(rsp.data);
        });
    };
    $scope.editDoc = function(doc) {
        var url = '/rest/help?id='+doc.id;
        $http.get(url).success(function(rsp){
            $scope.editing = doc;
            editor.importFile($scope.editing.id, rsp.data.content);
            editor.preview();
        });
    };
    $scope.preview = function() {
        editor.preview();
    };
    $scope.edit = function() {
        editor.edit();
    };
    $scope.index = function() {
        var url = '/rest/help';
        $http.get(url).success(function(rsp){
            $scope.docs = rsp.data;
        });
    };
    $scope.index();
}]);
