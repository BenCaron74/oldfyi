 <!-- Footer -->
  <footer class="site-footer">
    <div class="site-footer-legal">© 2017 <a href="https://freeyourinbox.com">FreeYourInbox</a></div>
    <div class="site-footer-right"></div>
  </footer>
  <!-- Core  -->
  <script src="/global/vendor/babel-external-helpers/babel-external-helpers.js"></script>
  <script src="/global/vendor/jquery/jquery.js"></script>
  <script src="/global/vendor/tether/tether.js"></script>
  <script src="/global/vendor/bootstrap/bootstrap.js"></script>
  <script src="/global/vendor/animsition/animsition.js"></script>
  <script src="/global/vendor/mousewheel/jquery.mousewheel.js"></script>
  <script src="/global/vendor/asscrollbar/jquery-asScrollbar.js"></script>
  <script src="/global/vendor/asscrollable/jquery-asScrollable.js"></script>
  <script src="/global/vendor/ashoverscroll/jquery-asHoverScroll.js"></script>
  <script src="/global/vendor/waves/waves.js"></script>
  <!-- Plugins -->
  <script src="/global/vendor/switchery/switchery.min.js"></script>
  <script src="/global/vendor/intro-js/intro.js"></script>
  <script src="/global/vendor/screenfull/screenfull.js"></script>
  <script src="/global/vendor/slidepanel/jquery-slidePanel.js"></script>
  <script src="/global/vendor/select2/select2.min.js"></script>
  <script src="/global/vendor/slidepanel/jquery-slidePanel.js"></script>
  <script src="/global/vendor/bootstrap-markdown/bootstrap-markdown.js"></script>
  <script src="/global/vendor/marked/marked.js"></script>
  <script src="/global/vendor/to-markdown/to-markdown.js"></script>
  <script src="/global/vendor/aspaginator/jquery.asPaginator.min.js"></script>
  <script src="/global/vendor/bootbox/bootbox.js"></script>
  <script src="/global/vendor/asprogress/jquery-asProgress.min.js"></script>
  <script src="/global/vendor/toastr/toastr.min.js"></script>
  <script src="/js/toastr.min.js"></script>
  <!-- Scripts -->
  <script src="/global/js/State.js"></script>
  <script src="/global/js/Component.js"></script>
  <script src="/global/js/Plugin.js"></script>
  <script src="/global/js/Base.js"></script>
  <script src="/global/js/Config.js"></script>
  <script src="/base/assets/js/Section/Menubar.js"></script>
  <script src="/base/assets/js/Section/GridMenu.js"></script>
  <script src="/base/assets/js/Section/Sidebar.js"></script>
  <script src="/base/assets/js/Section/PageAside.js"></script>
  <script src="/base/assets/js/Plugin/menu.js"></script>
  <script src="/global/js/config/colors.js"></script>
  <script src="/base/assets/js/config/tour.js"></script>
  <script>
  Config.set('assets', '/base/assets');
  </script>
  <!-- Page -->
  <script src="/base/assets/js/Site.js"></script>
  <script src="/global/js/Plugin/asscrollable.js"></script>
  <script src="/global/js/Plugin/slidepanel.js"></script>
  <script src="/global/js/Plugin/switchery.js"></script>
  <script src="/global/js/Plugin/action-btn.js"></script>
  <script src="/global/js/Plugin/asselectable.js"></script>
  <script src="/global/js/Plugin/editlist.js"></script>
  <script src="/global/js/Plugin/select2.js"></script>
  <script src="/global/js/Plugin/aspaginator.js"></script>
  <script src="/global/js/Plugin/animate-list.js"></script>
  <script src="/global/js/Plugin/selectable.js"></script>
  <script src="/global/js/Plugin/material.js"></script>
  <script src="/global/js/Plugin/bootbox.js"></script>
  <script src="/base/assets/js/BaseApp.js"></script>
  <script src="/base/assets/js/App/Mailbox.js"></script>
  <script src="/js/mailbox.js?v=29"></script>
<?php
if (isset($rowp) && isset($rowp['progress']) && $rowp['progress'] < 100)
{
  echo '  <script>setTimeout(checkProgress, 5000);</script>';
}
?>

</body>
</html>
