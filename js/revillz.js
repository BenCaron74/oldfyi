var gradNum = 0;
var gradientCard = ["#e53935","#d81b60","#8e24aa","#5e35b1","#3949ab",
"#1e88e5","#039be5","#00acc1","#00897b","#43a047","#7cb342","#c0ca33",
"#fdd835","#ffb300","#fb8c00","#f4511e","#6d4c41","#757575","#546e7a"
]

function randGrad() {
  var x = 1 + Math.floor(Math.random() * 19);
  gradNum++;
  console.log(gradNum);
  return gradientCard[gradNum];
}

(function($) {
  $(document).ready(function() {
    $('.card-top').each(function() {
      $(this).css({
        background: randGrad()
      });
    });

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
$('.more-nav .ion-android-more-vertical').click(function() {
  if ($('.xs-nav').is(":visible")) {
    $('.xs-item').hide();
    $('.xs-nav').animate({
      bottom: '100%'
    }, 200, function(){
      $('.xs-nav').hide();
    })
  } else {
    $('.xs-nav').show();
    $('.xs-item').show().parent().animate({
      bottom: '62%'
    }, 200)
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
    $('.xs-nav').animate({
      bottom: '100%'
    }, 200, function(){
      $('.xs-nav').hide();
    })
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
// var cardArray = [];
// $(".card input").change(function() {
//   if (this.checked) {
//     cardArray.push($(this).parents('.card').attr('id'))
//   } else {
//     var i = cardArray.indexOf($(this).parents('.card').attr('id'));
//     if (i != -1) {
//       cardArray.splice(i, 1);
//     }
//   }
//   if (jQuery.isEmptyObject(cardArray)) {
//     $('.context-menu').animate({
//       right: '-10%'
//     }, 200, function() {
//       $(this).hide();
//     });
//
//   } else {
//     $('.context-menu').show().animate({
//       right: '2%'
//     }, 200);
//   }
// });
//
// $('.context-menu i').mouseenter(function() {
//   $(this).prev().fadeIn('fast');
// }).mouseleave(function() {
//   $(this).prev().fadeOut();
// });
//
// $('#uncheckAll').click(function() {
//   $('input:checkbox:checked').prop('checked', false);
//   $('.context-menu').animate({
//     right: '-10%'
//   }, 300, function() {
//     $('.context-menu').hide()
//   })
// });
// $('#blacklistSelected').click(function() {
//   $('.card input:checkbox:checked').each(function() {
//     $(this).parents('.col-md-4').fadeOut('fast', function(){
//       $(this).remove();
//       $('.context-menu').animate({
//         right: '-10%'
//       }, 300, function() {
//         $('.context-menu').hide()
//       })
//     });
//   });
// });
// $('#whitelistSelected').click(function() {
//   $('.card input:checkbox:checked').each(function() {
//     $(this).parents('.col-md-4').fadeOut('fast', function(){
//       $(this).remove();
//       $('.context-menu').animate({
//         right: '-10%'
//       }, 300, function() {
//         $('.context-menu').hide()
//       })
//     });
//   });
// });
// $('#digestSelected').click(function() {
//   $('.card input:checkbox:checked').each(function() {
//     $(this).parents('.col-md-4').fadeOut('fast', function(){
//       $(this).remove();
//       $('.context-menu').animate({
//         right: '-10%'
//       }, 300, function() {
//         $('.context-menu').hide()
//       })
//     });
//   });
// });

$('#setting').click(function() {
  var item = $(this);
  var offset = item.offset();
  if ($('.settings-pane').is(":visible")) {
    $('.settings-pane').fadeOut('fast')
  } else {
    console.log(offset.top);
    console.log(offset.left);
    $('.settings-pane').css({
      left: offset.left - 310 +'px',
      top: offset.top + 'px',
      position:'fixed'
    }).fadeIn('fast');
  }
});
$('#notif').click(function() {
  var item = $(this);
  var offset = item.offset();
  if ($('.notification-pane').is(":visible")) {
    $('.notification-pane').fadeOut('fast')
  } else {
    console.log(offset.top);
    console.log(offset.left);
    $('.notification-pane').css({
      left: offset.left - 310 +'px',
      top: offset.top + 'px',
      position:'fixed'
    });
    $('.notification-pane').fadeIn('fast');
  }
});


function progress() {
    var progr = document.getElementById('progress');
    var progress = 0;
    var id = setInterval(frame, 50);
    function frame() {
        if (progress > $('#loader').width()) {
            clearInterval(id);
            $("#loader").fadeOut('fast', function(){
              $("#pNew").fadeIn('fast');
            });
        } else {
                progress += 25;
                //Set progress += 5;
                progr.style.width = progress + 'px';
            }
    }
}
progress();

$('.select').on('click','.placeholder',function(){
  var parent = $(this).closest('.select');
  if ( ! parent.hasClass('is-open')){
    parent.addClass('is-open');
    $('.select.is-open').not(parent).removeClass('is-open');
  }else{
    parent.removeClass('is-open');
  }
}).on('click','ul>li',function(){
  var parent = $(this).closest('.select');
  parent.removeClass('is-open').find('.placeholder').text( $(this).text() );
  parent.find('input[type=hidden]').attr('value', $(this).attr('data-value') );
});

$('#otherAct').click(function(){
  $(this).toggleClass('flip-action');
  if($('.card-content-overlay').css('display') == 'none'){
    $(this).parents('.card-top').next().find('.card-img').fadeOut();
    $(this).parents('.card-top').next().find('.card-txt').fadeOut(function(){
      $(this).parents('.card-content').children().first().slideDown();
    });
  } else {
    $(this).parents('.card-top').next().find('.card-content-overlay').slideUp(function(){
      $(this).nextAll().fadeIn();
    });
  }
});
$.fn.extend({
    revealAction: function (animationName) {
        var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
        this.addClass('animated ' + animationName).one(animationEnd, function() {
            $(this).removeClass('animated ' + animationName);
        });
        return this;
    }
});
$('#blacklistAct').click(function() {
  $(this).parents('.card').fadeOut(function(){
    $(this).parent().remove();
  })
});
$('#whitelistAct').click(function() {
  $(this).parents('.card').fadeOut(function(){
    $(this).parent().remove();
  })
});
$('#digestAct').click(function() {
  $(this).parents('.card').fadeOut(function(){
    $(this).parent().remove();
  })
});
$('#suggestAct, #deleteAct').click(function() {
  $(this).parents('.card').fadeOut(function(){
    $(this).parent().remove();
  })
});
$('#modalActionActive').click(function(){
  $('.modal-action-overlay, .modal-action-content').fadeIn('fast');
})
$('.modal-action-overlay').click(function(){
  $('.modal-action-overlay, .modal-action-content').fadeOut('fast');
})
