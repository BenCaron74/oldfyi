var userLang = navigator.language || navigator.userLanguage;
switch (userLang) {
  case 'fr':
    var transFile = "lang/fr/french.json";
    loadTxt(transFile);
    break;
  case 'en':
    var transFile = "lang/en/english.json";
    loadTxt(transFile);
    break;
  default:
    var transFile = "lang/en/english.json";
    loadTxt(transFile);
}

function loadTxt(file) {
  $.get(file, {}, function(data) {
    var template = $('#tpl').html();
    Mustache.parse(template);
    var rendered = Mustache.render(template, data);
    $('#tpl').html(rendered);
  }, 'json');
}
$(document).ready(function() {

});
