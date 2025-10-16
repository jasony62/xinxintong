requirejs(['/static/js/tms.bootstrap.js'], function (tms) {
  var _oRawPathes
  _oRawPathes = {
    js: {
      domReady: '/static/js/domReady',
      frame: '/views/default/pl/fe/matter/link/frame',
    },
    html: {
      timerNotice: '/views/default/pl/fe/_module/timerNotice',
    },
  }
  tms.bootstrap(_oRawPathes)
})
