jQuery(document).ready(function ($) {
  let url = (location.href)
  if (url === 'http://wp/wp-admin/edit-tags.php?taxonomy=position') {
    $('li.menu-icon-post').removeClass('wp-has-current-submenu').addClass('wp-not-current-submenu');
    $('li.toplevel_page_skills-matrix').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
    $("li.wp-has-submenu.toplevel_page_skills-matrix > a").removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
    $("li.wp-has-submenu.menu-icon-post > a").removeClass('wp-has-current-submenu').addClass('wp-not-current-submenu');
  }
})