jQuery(document).ready(function ($) {
  jQuery('select.level_developer').change(function () {
    jQuery(this).addClass('sdf');
    var data = {
      level: jQuery('.sdf :selected').val(),
      developer: jQuery(this).data('developer'),
      skill: jQuery(this).data('skill'),
      action: 'hello',
    }

    jQuery.post(myPlugin.ajaxurl , data, function (response) {
      // alert(response);
    });
    jQuery(this).removeClass('sdf');
  })

});