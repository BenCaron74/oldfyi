$(document).ready(function() {
  setTimeout(function() {
    new WOW().init();
    $('.loader').fadeOut(1000, function() {
      $(this).remove();
    });
  }, 1000);
});
