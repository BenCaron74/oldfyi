 var userLang = navigator.language || navigator.userLanguage;
 switch (userLang) {
   case 'fr':
     //var transFile = "lang/fr/french.json";
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
   $.getJSON(transFile, function(json) {
     var template = $('#template').html();
     Mustache.parse(template);
     var rendered = Mustache.render(template, json);
     $('#template').html(rendered);
   });
 }
