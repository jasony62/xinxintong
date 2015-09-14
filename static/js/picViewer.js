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
                hammertime = Hammer(elImg, {});
                hammertime.get('pan').set({
                    direction: Hammer.DIRECTION_ALL
                });
                hammertime.get('pinch').set({
                    enable: true
                });
                hammertime.on('swipeleft', function(event) {
                    options.next && options.next();
                }).on('swiperight', function(event) {
                    options.prev && options.prev();
                });
                hammertime.on('pinchstart', function(event) {
                    event.preventDefault();
                    scaleX = event.center.x;
                    scaleY = event.center.y;
                }).on('pinchmove', function(event) {
                    event.preventDefault();
                    var deltaScale;
                    deltaScale = event.scale;
                    if (lastScale * deltaScale > maxScale) {
                        if (lastScale === maxScale) {
                            return;
                        }
                        deltaScale = maxScale / lastScale;
                    } else if (lastScale * deltaScale < minScale) {
                        if (lastScale === minScale) {
                            return;
                        }
                    }
                    currentScale = lastScale * deltaScale;
                    toX = scaleX - (scaleX - lastX) * deltaScale;
                    toY = scaleY - (scaleY - lastY) * deltaScale;
                    transform();
                }).on('pinchend', function(event) {
                    event.preventDefault();
                    lastScale = currentScale;
                    lastX = toX;
                    lastY = toY;
                });
                hammertime.on('panstart', function(event) {
                    event.preventDefault();
                    lastX = toX;
                    lastY = toY;
                }).on('panmove', function(event) {
                    var gesture = event.gesture;
                    event.preventDefault();
                    toX = lastX + (event.deltaX / currentScale);
                    toY = lastY + (event.deltaY / currentScale);
                    transform();
                }).on('panend', function(event) {
                    event.preventDefault();
                });
                hammertime.on('tap', function(event) {
                    options.close && options.close();
                });
            }
            setOrigin(0, 0);
            transform();
        };
        return this;
    };
})(window);