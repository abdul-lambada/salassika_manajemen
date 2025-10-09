<?php
$BASE = defined('APP_URL') ? APP_URL : '';
?>
<!-- Core JS (CDNs) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
// jQuery + Bootstrap loader with multi-CDN fallback and dependency guarantee
(function() {
  function loadScript(src, onload, onerror) {
    var s = document.createElement('script');
    s.src = src; s.async = true;
    if (onload) s.onload = onload;
    if (onerror) s.onerror = onerror;
    document.head.appendChild(s);
    return s;
  }

  function ensureJqueryAndBootstrap() {
    // Step 1: Ensure jQuery
    function afterJQ() {
      // Step 2: Load Bootstrap bundle after jQuery exists
      function loadBootstrapPrimary() {
        loadScript('https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js', null, function() {
          // Try fallback CDNJS
          loadScript('https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js', null, function(){
            // Try local vendor fallback
            loadScript('<?= $BASE ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js');
          });
        });
      }
      if (!window.jQuery || !window.jQuery.fn) {
        // If jQuery still not present, load fallback then Bootstrap
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', loadBootstrapPrimary, function(){
          // Try local vendor jQuery then Bootstrap
          loadScript('<?= $BASE ?>/assets/vendor/jquery/jquery-3.7.1.min.js', loadBootstrapPrimary, function(){
            // If even fallback jQuery fails, still try Bootstrap (may fail gracefully)
            loadBootstrapPrimary();
          });
        });
      } else {
        loadBootstrapPrimary();
      }
    }

    if (window.jQuery && window.jQuery.fn) {
      afterJQ();
    } else {
      // Give primary jQuery a moment; then fallback if needed
      setTimeout(afterJQ, 0);
    }
  }

  // Start the process as soon as possible
  ensureJqueryAndBootstrap();
})();

// Chart.js fallback if primary CDN fails (then CDNJS, then local)
(function() {
  function loadFallback() {
    if (window.Chart) return; // already available
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js';
    s.async = true;
    s.onload = function(){ console.info('Chart.js loaded from fallback CDN'); };
    s.onerror = function(){
      console.warn('CDNJS Chart.js failed, trying local vendor...');
      var l = document.createElement('script');
      l.src = '<?= $BASE ?>/assets/vendor/chartjs/Chart.min.js';
      l.async = true;
      l.onload = function(){ console.info('Chart.js loaded from local vendor'); };
      l.onerror = function(){ console.error('Failed to load Chart.js from all sources'); };
      document.head.appendChild(l);
    };
    document.head.appendChild(s);
  }
  // try a tick later to see if primary loaded
  window.addEventListener('load', function(){
    setTimeout(function(){ if (!window.Chart) loadFallback(); }, 0);
  });
})();
</script>

<!-- App Scripts (local) -->
<script src="<?= $BASE ?>/assets/js/sb-admin-2.min.js"></script>
<script src="<?= $BASE ?>/assets/js/enhanced-charts.js"></script>
<script src="<?= $BASE ?>/assets/js/mobile-enhancements.js"></script>
