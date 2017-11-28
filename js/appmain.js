$(document).ready(function() {
  setTimeout(function() {
    $('.preloader-bg').fadeOut(1000, function() {
      $(this).remove();
    });
  }, 1000);

  $('#action').click(function() {
    // if () {
    //
    // }
    console.log($('#demoRoute span').width());

    window['section'] = new Object();
    if (!$('#about').hasClass("hide")) {
      $('#demoRoute span').animate({
        width: '+=33.3%'
      }, 1200)
      section.current = 'about';
      section.next = 'firstFeature'
      hideHome(section)
    } else if (!$('#firstFeature').hasClass("hide")) {
      $('#demoRoute span').animate({
        width: '+=33.3%'
      }, 1200)
      section.current = 'firstFeature';
      section.next = 'secondFeature'
      hideHome(section)
    } else if (!$('#secondFeature').hasClass("hide")) {
      $('#demoRoute span').animate({
        width: '+=33.3%'
      }, 1200)
      section.current = 'secondFeature';
      section.next = 'early'
      hideHome(section)
    }
    // else if (!$('#thirdFeature').hasClass("hide")) {
    //   $('#demoRoute span').animate({
    //     width: '+=33.3%'
    //   }, 1200)
    //   section.current = 'thirdFeature';
    //   section.next = 'early'
    //   hideHome(section)
    // }
    else if (!$('#early').hasClass("hide")) {
      section.current = 'early';
      section.next = 'about';
      $('#demoRoute span').animate({
        width: '0%'
      }, 1200);
      hideHome(section)
    }


  });

  $('#access').click(function() {
    $('#getEarly').animate({
      bottom: '0'
    }, 500, function() {
      $('#form').fadeIn();
    })
  });

  $('#homeNav').click(function() {
    window['section'] = new Object();
    section.current = 'firstFeature';
    section.next = 'home';
    hideHome(section);
  });

  $('#featureNav').click(function() {
    window['section'] = new Object();
    section.current = 'about';
    section.next = 'firstFeature';
    hideHome(section);
  });

  $('#earlyNav').click(function() {
    window['section'] = new Object();
    section.current = 'firstFeature';
    section.next = 'earlyNav';
    hideHome(section);
  });

});



$.fn.extend({
  animateCss: function(animationName) {
    var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
    this.addClass('animated ' + animationName).one(animationEnd, function() {
      $(this).hide().removeClass('animated ' + animationName);
      $('#' + section.next + ' *').show();
      $('#' + section.current).addClass('hide');
      $('#' + section.next).hide().removeClass('hide').delay(200).fadeIn();
      if (section.next == 'about') {
        $('#homeNav').removeClass('nav__item').addClass('nav__item--current');
        $('#earlyNav').removeClass('nav__item--current').addClass('nav__item');
        $('#featureNav').removeClass('nav__item--current').addClass('nav__item');
      }
      if (section.next == 'early') {
        $('#homeNav').removeClass('nav__item--current').addClass('nav__item');
        $('#earlyNav').removeClass('nav__item').addClass('nav__item--current');
        $('#featureNav').removeClass('nav__item--current').addClass('nav__item');
      }
      if (section.next == 'firstFeature') {
        $('#homeNav').removeClass('nav__item--current').addClass('nav__item');
        $('#earlyNav').removeClass('nav__item--current').addClass('nav__item');
        $('#featureNav').removeClass('nav__item').addClass('nav__item--current');
      }
    });
    return this;
  }
});

function hideHome(section) {
  $('#' + section.current + ' h1').animateCss('zoomOut');
  $('#' + section.current + ' h2').animateCss('zoomOut');
  $('#' + section.current + ' #img-hero').animateCss('zoomOut');
  if (section.next != 'about') {
    $('#action').text('Next step')
  } else if (section.next == 'about') {
    $('#action').text("How it's work?")
  } else if (section.next == 'early') {
    $('#action').text('Home')
  }
}
