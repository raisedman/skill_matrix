jQuery(document).ready(function ($) {
  let allDevelopers = []
  let categoriesResponse = {}

  const data = {
    category: null,
    skill: null,
    developers: [],
  }

  var maneTabLe=$('.matrix tbody').clone();

  function updateSkill () {
    const id = $(this).data('developer')
    const level = $(this).val()
    const skill = $(this).data('skill')

    let params = {
      level,
      developer: id,
      skill,
      action: 'hello',
    }

    data.developers.find(
      developer => developer.id === id).skills[skill] = level

    $.post(ajaxurl, params, function (response) {
      //alert(response);
    })
  }

  $('select.level_developer').change(updateSkill)

  $('.button_position').click(function () {
    let thisdata = $(this).data('developer')
    let str = `input[data-developer='${thisdata}']`
    let textinput = $(str).val()
    var dat = {
      devposition: textinput,
      developer: $(this).data('developer'),
      action: 'addposition',
    }
    $.post(ajaxurl, dat, function (response) {
      // alert(response);
    })
    $(this).parent().text(textinput)
    $(this).remove()
    $(str).remove()
  })

  $('button.refresh').click(function () {
    $('.matrix tbody').remove();
    $('.matrix').append(maneTabLe);
  });

  let dataajax = {
    action: 'developers',
  }

  $.get(ajaxurl, dataajax, function (response) {
    let parseResponse = JSON.parse(response)
    data.developers = parseResponse.developers
    allDevelopers = parseResponse.developers
    categoriesResponse = parseResponse.categories_response.reduce(
      (res, category) => {
        const val = Object.entries(category)[0]
        res[val[0]] = val[1]
        return res
      }, {})

  })

  $('#button_developers').click(function () {
    const selected = $('#all_developers').val()
    data.developers = selected.map(
      id => allDevelopers.find(developer => developer.id == id))
    generateTable(data)
  })

  $('#positions').change(function () {
    const val = $(this).val()
    data.developers = allDevelopers.filter(
      developer => developer.position === val)
    generateTable(data)
  })

  skill_categories.onchange = function (event) {
    data.category = event.target.value
    data.skill = null
    generateTable(data)
  }

  all_skills_developers.onchange = function (event) {
    data.category = null
    data.skill = event.target.value
    generateTable(data)
  }

  function createSkillSelect (values, developer, skill) {
    const select = document.createElement('select')

    select.dataset.skill = skill
    select.dataset.developer = developer
    select.className = 'level_developer'
    for (let i = 0; i < values.length; i++) {
      const option = document.createElement('option')

      option.value = values[i]
      option.textContent = values[i]

      select.append(option)
    }

    return select
  }

  const VALUES = ['basic', 'none', 'good', 'excellent', 'expert', 'JESUS']

  function createCell (value) {
    const td = document.createElement('td')
    td.append(value)

    return td
  }

  function createRow (skill, developers) {
    const row = document.createElement('tr')

    row.append(createCell(skill))

    for (let i = 0; i < developers.length; i++) {
      const select = createSkillSelect(VALUES, developers[i].id, skill)

      select.value = developers[i].skills[skill]

      row.append(createCell(select))
    }

    return row
  }

  function createTable (data) {
    const tbody = document.createElement('tbody')

    let categories
    let skills

    if (data.category) {
      categories = [data.category]
      skills = categoriesResponse[data.category]
    }
    else if (data.skill) {
      for (category in categoriesResponse) {
        if (categoriesResponse[category].includes(data.skill)) {
          categories = [category]
          break
        }
      }
      skills = [data.skill]
    }
    else {
      categories = Object.keys(categoriesResponse)
      skills = Object.values(categoriesResponse).
        reduce((res, category) => res.concat(category))
    }

    for (let i = 0; i < skills.length; i++) {
      const row = createRow(skills[i], data.developers)

      tbody.append(row)
    }

    for (let i = 0, k = 0; k < skills.length; i++) {
      const categoryCell = createCell(categories[i])
      const rows = categoriesResponse[categories[i]].length

      categoryCell.rowSpan = rows
      tbody.children[k].prepend(categoryCell)
      k += rows
    }

    const head = document.createElement('tr')
    head.append(createCell('Skills category'))
    head.append(createCell('Skill'))

    const posRow = document.createElement('tr')
    posRow.append(createCell('Position'))
    posRow.append(createCell(''))

    for (let i = 0; i < data.developers.length; i++) {
      head.append(createCell(data.developers[i].name))
      posRow.append(createCell(data.developers[i].position))
    }

    tbody.prepend(posRow)
    tbody.prepend(head)

    return tbody
  }

  const matrix = document.querySelector('.matrix')

  function generateTable (data) {

    const tbody = createTable(data)
    matrix.firstElementChild.remove()
    matrix.append(tbody)
    $('select.level_developer').change(updateSkill)
  }

})