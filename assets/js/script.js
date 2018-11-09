jQuery(document).ready(function ($) {
  var key = true
  $('button.showPass').click(function () {

    if (key) {
      $('.show_pass_text').text('Hide');
      $('#passwordDev').attr('type', 'text')
      key = false
    }
    else {
      $('.show_pass_text').text('Show');
      $('#passwordDev').attr('type', 'password')
      key = true
    }
  })

})