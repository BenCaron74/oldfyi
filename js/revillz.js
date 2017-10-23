var gradientCard = [
  "linear-gradient(to right, #00b09b, #96c93d)",
  "linear-gradient(to right, #800080, #ffc0cb)",
  "linear-gradient(to right, #fc4a1a, #f7b733)",
  "linear-gradient(to right, #e1eec3, #f05053)",
  "linear-gradient(to right, #74ebd5, #acb6e5)",
  "linear-gradient(to right, #22c1c3, #fdbb2d)",
  "linear-gradient(to right, #ff9966, #ff5e62)",
  "linear-gradient(to right, #7f00ff, #e100ff)",
  "linear-gradient(to right, #d9a7c7, #fffcdc)",
  "linear-gradient(to right, #642b73, #c6426e)"
]

function randGrad() {
  var x = Math.floor((Math.random() * 10) + 0);
  return gradientCard[x];
}

(function($) {
  $(document).ready(function() {
    // $('.card-top').each(function() {
    //   $(this).css({
    //     background: randGrad()
    //   });
    // });

    var winWidth = $(window).width();
    var offset = $(".header-brand").offset().top;
    if (offset > 140) {
      $(".header-brand .header-nav").show();
    }
    $(document).scroll(function() {
      var scrollTop = $(document).scrollTop();
      if (scrollTop > 140 && winWidth > 824) {
        $(".header-brand .header-nav").fadeIn("slow");

      } else {
        $(".header-brand .header-nav").fadeOut("fast");
      }
    });

  });
})(jQuery);


$('a').click(function() {
  if ($(this).parent().hasClass('nav-item')) {
    var active = '#' + $("[class*='nav-active']").attr('id');
    var id = $(this).parent().attr('id');
    var idNav = '#' + id;
    var idContent = '#p' + id.substr(1);
    var id = {
      nav: idNav,
      content: idContent
    };
    sectionSwitch(id, active);
  } else {
    return false;
  }
});

function sectionSwitch(id, active) {
  var selectedId = $(id.content);
  var pActive = '#p' + active.substr(2);
  var pActive = $(pActive);
  var nActive = $(active);
  $("li").each(function(index) {
    if ($(this).hasClass('nav-active')) {
      $(this).removeClass('nav-active');;
    }
  });
  pActive.fadeOut('fast', function() {
    selectedId.fadeIn();
    $("li").each(function(index) {
      if ($(this).is(id.nav)) {
        $(this).addClass("nav-active");
      }
    });
  });
}

$('.card-bottom li b').each(function() {
  var val = $(this);
  if (val.text().includes('%')) {
    var percent = val.text().replace("%", '');
    if (percent < 33) {
      val.css('color', '#43A047')
    } else if (percent < 66) {
      val.css('color', '#FFB300')
    } else {
      val.css('color', '#DD2C00')
    }
  } else if (val.text() < 33) {
    val.css('color', '#43A047')
  } else if (val.text() < 66) {
    val.css('color', '#FFB300')
  } else {
    val.css('color', '#DD2C00')
  }
});
var cardArray = [];
$(".card input").change(function() {
  if (this.checked) {
    cardArray.push($(this).parents('.card').attr('id'))
  } else {
    var i = cardArray.indexOf($(this).parents('.card').attr('id'));
    if (i != -1) {
      cardArray.splice(i, 1);
    }
  }
  if (jQuery.isEmptyObject(cardArray)) {
    $('.context-menu').animate({
      right: '-10%'
    }, 200, function() {
      $(this).hide();
    });

  } else {
    $('.context-menu').show().animate({
      right: '2%'
    }, 200);
  }
});

$('.context-menu i').mouseenter(function() {
  $(this).prev().fadeIn('fast');
}).mouseleave(function() {
  $(this).prev().fadeOut();
});

$('#uncheckAll').click(function() {
  $('input:checkbox:checked').prop('checked', false);
  $('.context-menu').animate({
    right: '-10%'
  }, 300, function() {
    $('.context-menu').hide()
  })
});
$('#blacklistSelected').click(function() {
  $('.card input:checkbox:checked').each(function() {
    $(this).parents('.col-md-4').fadeOut('fast', function(){
      $(this).remove();
      $('.context-menu').animate({
        right: '-10%'
      }, 300, function() {
        $('.context-menu').hide()
      })
    });
  });
});
$('#whitelistSelected').click(function() {
  $('.card input:checkbox:checked').each(function() {
    $(this).parents('.col-md-4').fadeOut('fast', function(){
      $(this).remove();
      $('.context-menu').animate({
        right: '-10%'
      }, 300, function() {
        $('.context-menu').hide()
      })
    });
  });
});
$('#digestSelected').click(function() {
  $('.card input:checkbox:checked').each(function() {
    $(this).parents('.col-md-4').fadeOut('fast', function(){
      $(this).remove();
      $('.context-menu').animate({
        right: '-10%'
      }, 300, function() {
        $('.context-menu').hide()
      })
    });
  });
});
