# Third-party notices

Solus Explorer includes, or loads at runtime, the third-party components listed below.
Each one stays under its own license. Everything not listed here is part of Solus
Explorer and is covered by the repository's [LICENSE](LICENSE).

No Material Design for Bootstrap (MDB) code is included.

## Bundled in this repository

| Component | Version | License | Where | Upstream |
|---|---|---|---|---|
| Bootstrap | 4.3.1 | MIT | `public/js/bootstrap.min.js`, `public/css/bootstrap.min.css` | https://getbootstrap.com |
| Popper.js | 1.14 (as shipped with Bootstrap 4.3) | MIT | `public/js/popper.min.js` | https://popper.js.org |
| DataTables core and Bootstrap 4 integration | 1.13.11 | MIT | `public/js/addons/jquery.dataTables.min.js`, `public/js/addons/dataTables.bootstrap4.min.js`, `public/css/addons/dataTables.bootstrap4.min.css` | https://datatables.net |
| Chart.js | 2.9.4 | MIT | `public/js/Chart.min.js` | https://www.chartjs.org |
| easy-pie-chart | 2.1.7 | MIT | `public/js/jquery.easypiechart.min.js` | https://github.com/rendro/easy-pie-chart |
| download.js | 4.21 | MIT | `public/js/addons/download.min.js` | https://github.com/rndme/download |
| qrcode-svg | 1.1.0 | MIT | `public/js/qrcode.min.js` | https://github.com/papnkukn/qrcode-svg |
| toastr | 2.1.4 | MIT | `public/js/toastr.min.js`, `public/css/toastr.min.css` (loaded only while a subscription notice is shown) | https://github.com/CodeSeven/toastr |
| Font Awesome Free | 5.15.4 | Icons CC BY 4.0, fonts SIL OFL 1.1, code MIT | `public/css/all.min.css`, `public/webfonts/fa-*` | https://fontawesome.com |
| Roboto | 2.x | Apache-2.0 | `public/font/roboto/*` | https://github.com/googlefonts/roboto |
| websocket-php | 1.x (single file) | ISC | `lib/websocketClient.php` | https://github.com/Textalk/websocket-php |
| Bech32 reference code (BitWasp port, extended with bech32m) | — | Unlicense | `lib/bech32.php` | https://github.com/Bit-Wasp/bech32 |

`public/font/icons/*` and `public/css/icons.css` are the project's icon font, generated with
Glyphter from the project's own glyphs.

## Loaded from a CDN at runtime

These are not part of the repository; the browser fetches them from the URL shown,
checked against a Subresource Integrity (SHA-384) hash in the script tag.

| Component | Version | License | URL |
|---|---|---|---|
| jQuery | 3.7.1 | MIT | `https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js` |
| perfect-scrollbar | 1.4.0 | MIT | `https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.4.0/perfect-scrollbar.min.js` |
| amCharts 4 (`core.js`, `charts.js`, `themes/animated.js`), only on pages with amCharts charts | 4.10.40 | amCharts free license | `https://cdn.amcharts.com/lib/version/4.10.40/` |

The amCharts free license allows use at no cost on the condition that the small
amCharts logo stays visible on every chart. Operators who buy a commercial amCharts
license can remove it by setting `amcharts_license_key` in `settings.php`.

## Optional third-party services

These services are off by default and are only contacted when enabled in `settings.php`.
They are hosted services under their own terms, not redistributed code.

| Service | Enabled by |
|---|---|
| Google Tag Manager | `google_tag_id` |
| Sevio, Bitmedia, Google AdSense ad networks | `ads_enabled`, `ads_priority`, `ads_config` |
| InMobi Choice consent banner; InMobi's tag is **not included**, the operator saves it as `public/js/local/cmp.js` | `cmp_enabled`, `cmp_property_id`, `cmp_script` (with ads) |
| Trustpilot widget | `trustpilot_enabled` |
| Cloudflare Web Analytics beacon | `cloudflare_beacon_token` |

## Runtime requirements (not included)

PHP 8.1+ with the `mongodb` (Apache-2.0) and `decimal` (MIT) extensions, MongoDB
server, Apache httpd, and the coin daemon. They are installed separately and are not
distributed with Solus Explorer.

## License texts

- MIT: the copyright and permission notice is kept at the top of each minified file.
  The full text is the same as the repository's [LICENSE](LICENSE) with the respective
  copyright holder: Bootstrap Authors and Twitter, Inc.; OpenJS Foundation and other
  contributors (jQuery); Federico Zivolo (Popper.js); SpryMedia Ltd (DataTables);
  Chart.js Contributors; Robert Fleischmann (easy-pie-chart); dandavis (download.js);
  Papnkukn (qrcode-svg); John Papa, Hans Fjällemark and Tim Ferrell (toastr);
  Fonticons, Inc. (Font Awesome code).
- Apache-2.0: [LICENSES/Apache-2.0.txt](LICENSES/Apache-2.0.txt) (Roboto).
- SIL OFL 1.1: [LICENSES/OFL-1.1.txt](LICENSES/OFL-1.1.txt) (Font Awesome fonts).
- CC BY 4.0: https://creativecommons.org/licenses/by/4.0/ (Font Awesome icons,
  attribution given above).
- ISC: kept in the header of `lib/websocketClient.php` (Textalk, Ignas Bernotas).
- Unlicense: public domain dedication, no notice required.
