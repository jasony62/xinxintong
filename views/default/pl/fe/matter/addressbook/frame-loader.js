require.config({
  paths: {
    "domReady": '/static/js/domReady',
    "frame": '/views/default/pl/fe/matter/addressbook/frame',
  },
  urlArgs: "bust=" + (new Date()).getTime()
});
require(['frame']);