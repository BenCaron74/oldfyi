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
  $('#gmail, #outlook, #yahoo, #other').animate({opacity: 0.50, height: "hide"}, {duration: 200, queue: false, done: function(){ $('svg').fadeIn(); } });
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
      if (email.toLowerCase().indexOf("@gmail.") < 0) {
        $('.login-title').text("Sorry!");
        $('.login-subtitle').text("Only Gmail accounts are currently supported, but don't go yet!");
        wip();
        $('#sry').fadeIn("slow");
        $('#home').fadeIn("slow");
      } else {
        $('.login-title').text("Thank you!");
        $('.login-subtitle').text("Your email address have been added to the signup queue");
        $('#after').fadeIn("slow");
        $('#home').fadeIn("slow");
      }
    });
  } else {
    alert('Please enter a valid email')
  }
}

function wip() {
  $('.w-work-modal').css({
    display: 'flex'
  });
  $(".w-work-modal").animate({
    top: '1em',
    opacity: 1
  }, 1000, function() {
    setTimeout(function() {
      $(".w-work-modal").animate({
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
}

$('#rip').click(function() {
  window.open('https://youtu.be/ylj0Xiiw1pM', '_blank');
});
