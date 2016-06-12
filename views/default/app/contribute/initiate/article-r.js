xxtApp.controller('initiateCtrl', ['$location', '$scope', 'http2', 'Article', 'Entry', 'Reviewlog', function($location, $scope, http2, Article, Entry, Reviewlog) {
    var mpid, id;
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    mpid = $location.search().mpid;
    id = $location.search().id;
    $scope.entry = $location.search().entry;
    $scope.Article = new Article('initiate', mpid, '');
    $scope.Entry = new Entry(mpid, $scope.entry);
    $scope.Reviewlog = new Reviewlog('initiate', mpid, {
        type: 'article',
        id: id
    });
    $scope.back = function(event) {
        event.preventDefault();
        location.href = '/rest/app/contribute/initiate?mpid=' + mpid + '&entry=' + $scope.entry;
    };
    $scope.shift2pc = function() {
        var ele = document.querySelector('#pagePopup iframe'),
            css, js;
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.entryApp.pageShift2Pc.html;
            css = document.createElement('style');
            css.innerHTML = $scope.entryApp.pageShift2Pc.css;
            ele.contentDocument.body.appendChild(css);
            js = document.createElement('script');
            js.innerHTML = $scope.entryApp.pageShift2Pc.js;
            ele.contentDocument.body.appendChild(js);
        }
        $('#pagePopup').show();
    };
    $scope.preview = function() {
        var url;
        url = '/rest/mi/matter?mode=preview&type=article&tpl=std&mpid=' + mpid + '&id=' + id;
        location.href = url;
    };
    $scope.Article.get(id).then(function(data) {
        $scope.editing = data;
        var ele = document.querySelector('#content>iframe');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.editing.body;
        }
    }).then(function() {
        $scope.Entry.get().then(function(data) {
            var i, j, ch, mapSubChannels = {};
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            for (i = 0, j = data.subChannels.length; i < j; i++) {
                ch = data.subChannels[i];
                mapSubChannels[ch.id] = ch;
            }
            for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                ch = $scope.editing.channels[i];
                mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
            }
        });
    });
    $scope.Reviewlog.list().then(function(data) {
        $scope.logs = data;
    });
}]);