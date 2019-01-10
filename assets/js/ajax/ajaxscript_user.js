
jQuery(document).ready(function ($) {
    $('select.level_developer').change(function () {
    $(this).addClass('sdf');
    var data = {
      level: $('.sdf :selected').val(),
      id: $(this).data('developer'),
      skill: $(this).data('skill'),
      action: 'changelevel'
    };

    $.post(myPlugin.ajaxurl , data, function (response) {
      // alert(response);
    });
    $(this).removeClass('sdf');
  })

});