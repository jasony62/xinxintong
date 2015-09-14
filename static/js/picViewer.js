(function(window) {
    window.PicViewer = function(selector, options) {

        options || (options = {});
        options.log || (options.log = function(msg) {});

        var hammertime;
        var vendorPrefixes = ["", "-webkit-", "-moz-", "-o-", "-ms-", "-khtml-"];
        var elImg;
        var viewHeight, viewWidth;
        var minScale, maxScale, currentScale, lastScale, scaleX, scaleY;
        var lastX, lastY, toX, toY;

        var imgHeight = function() {
            return elImg.height * currentScale;
        };
        var imgWidth = function() {
            return elImg.width * currentScale;
        };
        var transform = function() {
            var style, vendor, cssScale, cssTranslate, overrideWidth, overrideHeight;

            if (currentScale > maxScale) {
                return;
            }
            if (currentScale < minScale) currentScale = minScale;

            toX > 0 && (toX = 0);
            overrideWidth = Math.round(viewWidth - imgWidth());
            if (toX < overrideWidth) {
                toX = overrideWidth < 0 ? overrideWidth : overrideWidth / 2;
            }
            toY > 0 && (toY = 0);
            overrideHeight = Math.round(viewHeight - imgHeight());
            if (toY < overrideHeight) {
                toY = overrideHeight < 0 ? overrideHeight : overrideHeight / 2;
            }

            style = elImg.style, vendor;
            cssScale = "scale(" + currentScale + ")";
            cssTranslate = 'translate(' + toX + "px, " + toY + "px)"; //如果xy的值太小有可能使设置无效

            for (var i = 0, l = vendorPrefixes.length; i < l; i++) {
                vendor = vendorPrefixes[i];
                style[vendor + "transform"] = cssTranslate + ' ' + cssScale;
            };
            options.log(cssTranslate + ' ' + cssScale);
        };
        var setOrigin = function(x, y) {
            var style = elImg.style,
                vendor;
            for (var i = 0, l = vendorPrefixes.length; i < l; i++) {
                vendor = vendorPrefixes[i];
                style[vendor + "transform-origin"] = x + ' ' + y;
            };
            style["MozTransformOrigin"] = x + ' ' + y;
        };
        this.fresh = function() {
            elImg = document.querySelector(selector);
            viewHeight = elImg.parentNode.clientHeight, viewWidth = elImg.parentNode.clientWidth;
            minScale = Math.min(viewWidth / elImg.width, viewHeight / elImg.height);
            maxScale = Math.max(viewWidth * 3 / elImg.width, viewHeight * 3 / elImg.height);
            lastScale = currentScale = minScale;
            scaleX = scaleY = 0;
            lastX = lastY = toX = toY = 0;

            if (hammertime === undefined) {
                hammertime = Hammer(elImg, {}).
                on('swipeleft', function(event) {
                    options.next && options.next();
                }).
                on('swiperight', function(event) {
                    try {
                        options.prev && options.prev();
                    } catch (e) {
                        console.log('ex', e);
                    }
                });
            }
            setOrigin(0, 0);
            transform();
        };
        return this;
    };
})(window);