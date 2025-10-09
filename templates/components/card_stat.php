<?php
// Reusable statistic card component
// Expected variables:
// $border (primary|success|info|warning|danger|secondary)
// $icon (Font Awesome class, e.g., 'fas fa-users')
// $title_text (string)
// $value_html (string, can contain HTML)
if (!isset($border)) $border = 'primary';
if (!isset($icon)) $icon = 'fas fa-info-circle';
if (!isset($title_text)) $title_text = 'Title';
if (!isset($value_html)) $value_html = '0';
?>
<div class="card border-left-<?= htmlspecialchars($border) ?> shadow h-100 py-2">
  <div class="card-body">
    <div class="row no-gutters align-items-center">
      <div class="col mr-2">
        <div class="text-xs font-weight-bold text-<?= htmlspecialchars($border) ?> text-uppercase mb-1">
          <?= htmlspecialchars($title_text) ?>
        </div>
        <div class="h5 mb-0 font-weight-bold text-gray-800">
          <?= $value_html ?>
        </div>
      </div>
      <div class="col-auto">
        <i class="<?= htmlspecialchars($icon) ?> fa-2x text-gray-300"></i>
      </div>
    </div>
  </div>
</div>
