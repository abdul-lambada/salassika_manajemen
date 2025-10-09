# Local Vendor Fallbacks

Place offline copies here to enable local fallbacks when CDNs are unavailable.

Expected paths used by templates/scripts.php:

- jQuery: `assets/vendor/jquery/jquery-3.7.1.min.js`
- Bootstrap (Bundle): `assets/vendor/bootstrap/bootstrap.bundle.min.js` (v4.6.x)
- Chart.js: `assets/vendor/chartjs/Chart.min.js` (v2.9.4)

You can download exact versions from:
- jQuery: https://code.jquery.com/
- Bootstrap: https://github.com/twbs/bootstrap/releases/tag/v4.6.2
- Chart.js: https://github.com/chartjs/Chart.js/releases/tag/v2.9.4

Once files are copied, no code changes are needed; the loader will auto-fallback to these local files.
