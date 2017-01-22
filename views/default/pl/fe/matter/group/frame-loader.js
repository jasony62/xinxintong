require.config({
    paths: {
        "domReady": '/static/js/domReady',
        "frame": '/views/default/pl/fe/matter/group/frame',
    },
    urlArgs: "bust=" + (new Date() * 1)
});
require(['frame']);
