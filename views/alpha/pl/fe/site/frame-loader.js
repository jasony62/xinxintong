require.config({
    waitSeconds: 0,
    paths: {
        "domReady": '/static/js/domReady',
        "frame": '/views/default/pl/fe/site/frame',
    },
    urlArgs: "bust=" + (new Date * 1)
});
require(['frame']);