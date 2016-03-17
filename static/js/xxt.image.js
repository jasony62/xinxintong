(function() {
    window.xxt === undefined && (window.xxt = {});
    window.xxt.image = {
        options: {},
        choose: function(deferred, from) {
            var promise, imgs = [];
            promise = deferred.promise;
            if (window.wx !== undefined) {
                window.wx.chooseImage({
                    success: function(res) {
                        var i, img;
                        for (i in res.localIds) {
                            img = {
                                imgSrc: res.localIds[i]
                            };
                            imgs.push(img);
                        }
                        deferred.resolve(imgs);
                    }
                });
            } else if (window.YixinJSBridge) {
                window.YixinJSBridge.invoke(
                    'pickImage', {
                        type: from,
                        quality: 100
                    },
                    function(result) {
                        var img;
                        if (result.data && result.data.length) {
                            img = {
                                imgSrc: 'data:' + result.mime + ';base64,' + result.data
                            };
                            imgs.push(img);
                        }
                        deferred.resolve(imgs);
                    });
            } else {
                var ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f, type;
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        type = {
                            ".jp": "image/jpeg",
                            ".pn": "image/png",
                            ".gi": "image/gif"
                        }[f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                        f.type2 = f.type || type;
                        var reader = new FileReader();
                        reader.onload = (function(theFile) {
                            return function(e) {
                                var img = {};
                                img.imgSrc = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                                imgs.push(img);
                                deferred.resolve(imgs);
                            };
                        })(f);
                        reader.readAsDataURL(f);
                    }
                }, false);
                ele.click();
            }
            return promise;
        },
        wxUpload: function(deferred, img) {
            var promise;
            promise = deferred.promise;
            if (0 === img.imgSrc.indexOf('weixin://') || 0 === img.imgSrc.indexOf('wxLocalResource://')) {
                window.wx.uploadImage({
                    localId: img.imgSrc,
                    isShowProgressTips: 1,
                    success: function(res) {
                        img.serverId = res.serverId;
                        deferred.resolve(img);
                    }
                });
            } else {
                deferred.resolve(img);
            }
            return promise;
        }
    };
})();