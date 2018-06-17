$(function () {
  $('.js-btn-export').click(function () {
    $('.js-export-spinner').removeClass('hidden')

    // Start polling for the cookie showing that the export has finished.
    // http://stackoverflow.com/questions/1106377/detect-when-browser-receives-file-download
    var poll = window.setInterval(function () {
      var token = getCookie('eventhelperExportFinished')

      if (token) {
        $('.js-export-spinner').addClass('hidden')
        expireCookie('eventhelperExportFinished')
        window.clearInterval(poll)
      }
    }, 1000)
  })

  function getCookie(name) {
    var parts = document.cookie.split(name + '=')
    if (parts.length === 2) {
      return parts.pop().split(';').shift()
    }
  }

  function expireCookie(cName) {
    document.cookie =
      encodeURIComponent(cName) +
      '=deleted; expires=' +
      new Date(0).toUTCString()
  }
})
