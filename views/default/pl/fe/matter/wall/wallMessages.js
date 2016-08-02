onmessage = function(event) {
    //console.log(event);
    var id = event.data.id,
        last = event.data.last;
        site = event.data.site;
    doAjax('GET', '/rest/pl/fe/matter/wall/message/pendingList?id=' +  id + '&last=' + last + '&site=' + site, null, function(rsp) {
        postMessage(rsp.data[0]);
        last = rsp.data[1];
        setInterval(function() {
            doAjax('GET', '/rest/pl/fe/matter/wall/message/pendingList?id=' +  id  + '&last=' + last +'&site=' + site , null, function(rsp) {
                postMessage(rsp.data[0]);
                last = rsp.data[1];
            });
        }, 5000);
    });
};

function doAjax(type, url, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open(type, url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=UTF-8");
    xhr.setRequestHeader("Accept", "application/json");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    if (callback) {
                        var rsp = xhr.responseText;
                        var obj = eval("(" + rsp + ')');
                        callback(obj);
                    }
                } catch (e) {
                    alert('E2:' + e.toString());
                }
            } else {
                alert('E3:' + xhr.statusText);
            }
        }
    };
    xhr.send(data ? data : null);
}