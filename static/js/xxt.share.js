(function() {
    var setWxShare = function(title, link, desc, img) {
        window.wx.onMenuShareTimeline({
            title: window.xxt.share.options.descAsTitle ? desc : title,
            link: link,
            imgUrl: img,
            success: function() {
                try {
                    window.xxt.share.options.logger && window.xxt.share.options.logger('T');
                } catch (ex) {
                    alert('share failed:' + ex.message);
                }
            },
            cancel: function() {}
        });
        window.wx.onMenuShareAppMessage({
            title: title,
            desc: desc,
            link: link,
            imgUrl: img,
            success: function() {
                try {
                    window.xxt.share.options.logger && window.xxt.share.options.logger('F');
                } catch (ex) {
                    alert('share failed:' + ex.message);
                }
            },
            cancel: function() {}
        });
    };
    window.xxt === undefined && (window.xxt = {});
    window.xxt.share = {
        options: {},
        set: function(title, link, desc, img, fnOther) {
            if (/Android/i.test(navigator.userAgent) || /iPhone/i.test(navigator.userAgent) || /iPad/i.test(navigator.userAgent)) {
                img && img.length && img.indexOf(location.protocol) === -1 && (img = location.protocol + '//' + location.hostname + img);
                if (/MicroMessenger/i.test(navigator.userAgent) && window.wx !== undefined) {
                    window.wx.ready(function() {
                        setWxShare(title, link, desc, img);
                    });
                } else if (fnOther && typeof fnOther === 'function') {
                    fnOther(title, link, desc, img);
                }
            }
        }
    };
    window.define && define(function() {
        return window.xxt.share;
    });
})();