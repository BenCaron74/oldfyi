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
// $("#seek").on("click", function() {
//   console.log('aa');
// });
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
    console.log(offset);
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
