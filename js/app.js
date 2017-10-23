$(document).ready(function() {
  new WOW().init();
  setTimeout(function() {
    $(".info-modal").animate({
      top: '-10em',
      opacity: 0.2
    }, 1000, function() {
      $(this).hide();
      $(this).css({
        opacity: 1
      })
    });
  }, 8000);
});

$('#gmail').click(function() {
  $('#gmail').animateCss('fadeOut');
  $('#outlook').animateCss('zoomOut');
  $('#yahoo').animateCss('zoomOut');
  $('#other').animateCss('fadeOut');
  $('svg').fadeIn();
  document.location.href = "/newsletters";
});

$.fn.extend({
  animateCss: function(animationName) {
    var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
    this.addClass('animated ' + animationName).one(animationEnd, function() {
      $(this).removeClass('animated ' + animationName);
      $(this).hide();
    });
    return this;
  }
});

$('#submit').click(function() {
  isEmail();
});

$('#email').keyup(function(e) {
  if (e.keyCode == 13) {
    isEmail();
  }
});

function isEmail() {
  var email = $('#email').val();
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,63})+$/;
  if (regex.test(email) != false) {
    $('#before').fadeOut("slow", function() {
      $('.login-subtitle').text("You're now registered to the beta test program")
      $('#after').fadeIn("slow");
      $('#home').fadeIn("slow");
    });
  } else {
    alert('Please enter a valid email')
  }
}
