'use strict';
window.xxt === undefined && (window.xxt = {});
window.xxt.image = {
    options: {},
    canupload: function (file) {
        var seat, extension, allowtype = "png,jpg,jpeg,gif";
        if (!(file.name.lastIndexOf("."))) {
            var message = `只能上传以${allowtype}为扩展名的文件`;
            return {
                err_code: -1,
                err_msg: message
            };
        }
        seat = file.name.lastIndexOf(".") + 1;
        extension = file.name.substring(seat).toLowerCase();
        if (allowtype.indexOf(extension) === -1) {
            var message = `图片扩展名（${extension}）错误：只能上传以${allowtype}为扩展名的文件`;
            return {
                err_code: -1,
                err_msg: message
            };
        }
        return {
            err_code: 0
        };
    },
    choose: function (deferred, from) {
        var promise, imgs = [];
        promise = deferred.promise;
        var ele = document.createElement('input');
        ele.setAttribute('type', 'file');
        ele.addEventListener('change', function (evt) {
            var i, cnt, f, type;
            cnt = evt.target.files.length;
            for (i = 0; i < cnt; i++) {
                f = evt.target.files[i];
                var result = window.xxt.image.canupload(f);
                if (result.err_code === 0) {
                    type = {
                        ".jp": "image/jpeg",
                        ".pn": "image/png",
                        ".gi": "image/gif"
                    } [f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                    f.type2 = f.type || type;
                    var oReader = new FileReader();
                    oReader.onload = (function (theFile) {
                        return function (e) {
                            var img = {};
                            img.imgSrc = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                            imgs.push(img);
                            document.body.removeChild(ele);
                            deferred.resolve(imgs);
                        };
                    })(f);
                    oReader.readAsDataURL(f);
                } else {
                    deferred.resolve(result.err_msg);
                }
            }
        }, false);
        ele.style.opacity = 0;
        document.body.appendChild(ele);
        ele.click();

        return promise;
    },
    paste: function (oDiv, deferred, from) {
        var promise, imgs = [];
        promise = deferred.promise;
        oDiv.focus();

        function imgReader(item) {
            var blob = item.getAsFile(),
                reader = new FileReader();
            reader.onload = function (e) {
                var img = {};
                img.imgSrc = e.target.result;
                imgs.push(img);
                deferred.resolve(imgs);
            };
            reader.readAsDataURL(blob);
        };
        oDiv.addEventListener('paste', function (event) {
            // 通过事件对象访问系统剪贴板
            var clipboardData = event.clipboardData,
                items, item;
            if (clipboardData) {
                items = clipboardData.items;
                if (items && items.length) {
                    for (var i = 0; i < clipboardData.types.length; i++) {
                        if (clipboardData.types[i] === 'Files') {
                            item = items[i];
                            break;
                        }
                    }
                    if (item && item.kind === 'file' && item.type.match(/^image\//i)) {
                        imgReader(item);
                    }
                }
            }
        });
        return promise;
    },
    wxUpload: function (deferred, img) {
        var promise;
        promise = deferred.promise;
        if (0 === img.imgSrc.indexOf('weixin://') || 0 === img.imgSrc.indexOf('wxLocalResource://')) {
            window.wx.uploadImage({
                localId: img.imgSrc,
                isShowProgressTips: 1,
                success: function (res) {
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