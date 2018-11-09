jQuery(document).ready(function ($) {
  $('#all_developers').chosen({placeholder_text_multiple: 'Select Developers'})
  $('#skill_categories').chosen()
  $('#positions').chosen()

  let senddata = {
    action: 'senddev',
  }

  $.post(ajaxurl, senddata, function (response) {
    if (response.success) {
      console.log(response)
      let template = wp.template('my-template')
      let data = {
        developers: response.data[0],
        positions: response.data[1],
        arrayObj: response.data[2],
        skill_level: response.data[3],
      }
      jQuery('.my-element').html(template(data))
    }
    else {
      alert('error')
    }
  })

  $('#skill_categories').change(function () {
    let data = {
      action: 'categories',
      term: $(this).val(),
    }
    $.post(ajaxurl, data, function (response) {
      if (response.success) {
        $('div.skills-of-term').html(response.data)
      }
      else {
        alert('error')
      }
      $('#all_skills_developers').chosen()
    })

    const category = $('#skill_categories').val()
    const developers = $('#all_developers').val()
    data = {
      action: 'senddev',
      developers,
      category,
    }
    $('.my-element table').remove();
    $('.preloader-ajax').show();
    $.post(ajaxurl, data, function (response) {
      $('.preloader-ajax').hide();
      if (response.success) {
        console.log(response)
        let template = wp.template('my-template')
        let data = {
          developers: response.data[0],
          positions: response.data[1],
          arrayObj: response.data[2],
          skill_level: response.data[3],
        }
        jQuery('.my-element').html(template(data))
      }
      else {
        alert('filter error')
      }
    })
  })

  $('#all_developers').change(function () {
    const skill = $('#all_skills_developers').val()
    const category = $('#skill_categories').val()
    const developers = $('#all_developers').val()
    data = {
      action: 'senddev',
      developers,
      category,
      skill
    }
    $('.my-element table').remove()
    $('.preloader-ajax').show();
    $.post(ajaxurl, data, function (response) {
      $('.preloader-ajax').hide();
      if (response.success) {
        console.log(response)
        let template = wp.template('my-template')
        let data = {
          developers: response.data[0],
          positions: response.data[1],
          arrayObj: response.data[2],
          skill_level: response.data[3],
        }
        jQuery('.my-element').html(template(data))
      }
      else {
        alert('filter error')
      }
    })
  })

  $('body').on('change', '#all_skills_developers', function () {
    const skill = $('#all_skills_developers').val()
    const developers = $('#all_developers').val()
    const category = $('#skill_categories').val()
    data = {
      action: 'senddev',
      skill,
      developers,
      category
    }
    $('.my-element table').remove()
    $('.preloader-ajax').show();
    $.post(ajaxurl, data, function (response) {
      if (response.success) {
        console.log(response)
        let template = wp.template('my-template')
        let data = {
          developers: response.data[0],
          positions: response.data[1],
          arrayObj: response.data[2],
          skill_level: response.data[3],
        }
        $('.preloader-ajax').hide();
        jQuery('.my-element').html(template(data))
      }
      else {
        alert('filter error')
      }
    })
  })

  $('#positions').change(function () {
    const position = $('#positions').val()
    const category = $('#skill_categories').val()
    data = {
      action: 'senddev',
      position,
      category,
    }
    $('.my-element table').remove()
    $('.preloader-ajax').show();
    $.post(ajaxurl, data, function (response) {
      if (response.success) {
        console.log(response)
        let template = wp.template('my-template')
        let data = {
          developers: response.data[0],
          positions: response.data[1],
          arrayObj: response.data[2],
          skill_level: response.data[3],
        }
        $('.preloader-ajax').hide();
        jQuery('.my-element').html(template(data))
      }
      else {
        alert('filter error')
      }
    })
  })

  $('body').on('change', 'select.level_developer', function () {
    const id = $(this).data('developer')
    const level = $(this).val()
    const skill = $(this).data('skill')

    let params = {
      level,
      id,
      skill,
      action: 'changelevel',
    }
    $.post(ajaxurl, params, function (response) {
      if (response.success) {}
      else {
        alert('skill change error')
      }
    })
  })

})