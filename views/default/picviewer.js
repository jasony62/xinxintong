define(['hammer'], function(Hammer) {
    var eViewer, js, body;
    eViewer = document.createElement('div');
    eViewer.setAttribute('id', 'picViewer');
    eViewer.innerHTML = "<div><span class='page'></span><span class='prev'><i class='fa fa-angle-left'></i></span><span class='next'><i class='fa fa-angle-right'></i></span><span class='exit'><i class='fa fa-times-circle-o'></i></span></div><img>";
    document.body.appendChild(eViewer);
    body = document.querySelector('body');
    var eImgs, aImgs, currentIndex;
    aImgs = [];
    eImgs = document.querySelectorAll('.wrap img');

    var PicViewer = function(selector, options) {

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
    var oPicViewer, eCloser, ePage, ePrev, eNext, fnClickImg, fnSetActionStatus;
    ePage = document.querySelector('#picViewer span.page');
    ePrev = document.querySelector('#picViewer span.prev');
    eNext = document.querySelector('#picViewer span.next');
    eCloser = document.querySelector('#picViewer span.exit');

    function next() {
        if (currentIndex < aImgs.length - 1) {
            currentIndex++;
            eViewer.querySelector('img').src = aImgs[currentIndex].src;
            fnSetActionStatus();
        }
    };

    function prev() {
        if (currentIndex > 0) {
            currentIndex--;
            eViewer.querySelector('img').src = aImgs[currentIndex].src;
            fnSetActionStatus();
        }
    };

    function fnClose() {
        eViewer.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.body.removeEventListener('touchmove', fnStopMove, false);
        return false;
    };

    function fnStopMove(e) {
        e.preventDefault();
    };
    oPicViewer = PicViewer('#picViewer img', {
        next: next,
        prev: prev,
        close: fnClose
    });
    fnClickImg = function(event) {
        var top, height, src;
        event.preventDefault();
        currentIndex = aImgs.indexOf(this);
        top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
        height = document.documentElement.clientHeight;
        src = this.src;
        document.body.style.overflow = 'hidden';
        eViewer.style.top = top + 'px';
        eViewer.style.height = height + 1 + 'px';
        eViewer.style.display = 'block';
        eViewer.querySelector('img').src = src;
        oPicViewer.fresh();
        fnSetActionStatus();
        document.body.addEventListener('touchmove', fnStopMove, false);
    };
    fnSetActionStatus = function() {
        if (currentIndex === 0) {
            ePrev.classList.add('hide');
            eNext.classList.remove('hide');
        } else if (currentIndex === aImgs.length - 1) {
            ePrev.classList.remove('hide');
            eNext.classList.add('hide');
        } else {
            ePrev.classList.remove('hide');
            eNext.classList.remove('hide');
        }
        ePage.innerHTML = currentIndex + 1 + '/' + aImgs.length;
    };
    ePrev.addEventListener('click', function(e) {
        e.preventDefault();
        prev();
        return false;
    }, false);
    eNext.addEventListener('click', function(e) {
        e.preventDefault();
        next();
        return false;
    }, false);
    eCloser.addEventListener('click', fnClose, false);
    var img, i, l, indicator;
    for (i = 0, l = eImgs.length; i < l; i++) {
        img = eImgs[i];
        img.addEventListener('click', fnClickImg);
        indicator = document.createElement('i');
        indicator.classList.add('fa');
        indicator.classList.add('fa-search');
        img.parentNode.appendChild(indicator);
        img.parentNode.classList.add('wrap-img');
        aImgs.push(img);
    }
    window.addEventListener('resize', function() {
        var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
        var height = document.documentElement.clientHeight;
        eViewer.style.top = top + 'px';
        eViewer.style.height = height + 1 + 'px';
        if (eViewer.style.display === 'block') {
            oPicViewer.fresh();
        }
    });
});