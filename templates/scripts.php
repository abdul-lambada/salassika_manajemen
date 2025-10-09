<!-- Bootstrap core JavaScript-->
<?php $BASE = defined('APP_URL') ? APP_URL : ''; ?>
<script src="<?= $BASE ?>/assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= $BASE ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="<?= $BASE ?>/assets/vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="<?= $BASE ?>/assets/js/sb-admin-2.min.js"></script>
<script src="<?= $BASE ?>/assets/js/mobile-enhancements.js"></script>
<script src="<?= $BASE ?>/assets/js/enhanced-charts.js"></script>
<script>
// Sidebar accordion behavior (migrated from sidebar.php)
$(function() {
  // Collapse handling for sidebar grouped menus
  $(document).on('click', '.nav-link[data-toggle="collapse"]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $link = $(this);
    var target = $link.data('target');
    var isExpanded = $link.attr('aria-expanded') === 'true';
    // Close other groups
    $('.collapse.show').not(target).collapse('hide');
    $('.nav-link[data-toggle="collapse"]').not($link).attr('aria-expanded', 'false').addClass('collapsed');
    // Toggle current
    if (isExpanded) {
      $(target).collapse('hide');
      $link.attr('aria-expanded', 'false').addClass('collapsed');
    } else {
      $(target).collapse('show');
      $link.attr('aria-expanded', 'true').removeClass('collapsed');
    }
  });
  // Keep click inside collapse from bubbling
  $(document).on('click', '.collapse-item', function(e) {
    e.stopPropagation();
  });
});
</script>