<?php


if (strtolower(php_sapi_name()) === 'cli') {
    throw new \Exception('Please run this file via HTTP.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


$Url = new \PMTL\Libraries\Url();
$Assets = new \PMTL\Libraries\Assets($Url);
$htmlTitle = null; // customize html title for each page.
$customHTMLHead = '<link rel="stylesheet" href="' . $Assets->assetUrl('assets/css/index.css') . '">
<link rel="stylesheet" href="' . $Assets->assetUrl('assets/vendor/leaflet/leaflet.css', ['v' => '1.9.4']) . '">';
include 'HTTP/common/html-head.php';
$navbarExpand = 'md';
?>
        <nav id="pmtl-main-navbar" class=" navbar fixed-top navbar-expand-<?=$navbarExpand; ?> bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $Url->getAppBasePath(); ?>">Personal maps timeline</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="navbarSupportedContent" class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto mb-2 mb-<?=$navbarExpand; ?>-0 navbar-nav-scroll">
                        <li class="nav-item">
                            <a id="pmtl-open-timeline-panel" class="nav-link" title="Select a date" aria-label="Select a date"><i class="fa-solid fa-calendar"></i> <span class="d-<?=$navbarExpand; ?>-none">Select a date</span></a>
                        </li>
                        <li class="nav-item">
                            <a id="pmtl-open-search-panel" class="nav-link" title="Search" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i> <span class="d-<?=$navbarExpand; ?>-none">Search</span></a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="help.php" title="Help" aria-label="Help"><i class="fa-solid fa-circle-question"></i> <span class="d-<?=$navbarExpand; ?>-none">Help</span></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php unset($navbarExpand); ?> 
        <div class="pmtl-contents">
            <div id="pmtl-map" class="pmtl-is-loading">Loading &hellip;</div>
            <div id="pmtl-listing-panel">
                <div class="controls-row">
                    <div id="pmtl-listing-panel-resize" class="vertical-resize-controls" title="Resize panel">
                        <div class="resize-v-icon"></div>
                    </div>
                    <div class="buttons-controls">
                        <button id="pmtl-listing-panel-maxmin-btn" class="btn maxmin-controls" type="button" title="Minimize or maximize this panel"><i class="fa-regular fa-window-restore"></i></button>
                        <button id="pmtl-listing-panel-close-btn" class="btn close-controls" type="button" title="Close this panel"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div><!-- .controls-row -->
                <div id="pmtl-listing-panel-contents"></div><!-- #pmtl-listing-panel-contents -->
            </div><!-- #pmtl-listing-panel -->

            <!-- modal dialog below is for task such as edit place name. -->
            <div id="pmtl-bs-modal" class="modal fade" tabindex="-1" aria-labelledby="pmtl-bs-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="pmtl-bs-modal-title" class="modal-title"></h5>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="pmtl-bs-modal-loading"><i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading &hellip;</p>
                        </div>
                    </div>
                </div>
            </div><!-- #pmtl-bs-modal -->

        </div><!-- .pmtl-contents -->

        <template id="pmtl-timeline-panel-template">
            <div class="pmtl-timeline-panel-container container-fluid">
                <div class="pmtl-timeline-panel-select-date-row row g-0 mb-2 align-items-center">
                    <div class="col-2 text-start">
                        <button id="pmtl-timeline-control-date-previous" class="btn btn-sm" type="button">
                            <i class="fa-solid fa-angle-left"></i>
                        </button>
                    </div>
                    <div class="col text-center">
                        <input id="pmtl-timeline-control-date-input" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-2 text-end">
                        <button id="pmtl-timeline-control-date-next" class="btn btn-sm" type="button">
                            <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>
                </div>
                <div class="pmtl-timeline-panel-content-row row">
                    <div id="pmtl-timeline-panel-content-placeholder"></div>
                </div>
            </div>
        </template><!-- #pmtl-timeline-panel-template -->

        <template id="pmtl-search-panel-template">
            <div class="pmtl-search-panel-container container-fluid">
                <form id="pmtl-search-panel-form" class="mb-3">
                    <label class="visually-hidden" for="pmtl-search-input">Search keyword</label>
                    <input id="pmtl-search-input" class="form-control" list="pmtl-search-datalist" type="search" name="keyword" placeholder="Search place name">
                    <datalist id="pmtl-search-datalist"></datalist>
                </form>
                <div class="pmtl-search-panel-content-row row">
                    <div id="pmtl-search-panel-content-placeholder"></div>
                </div>
            </div>
        </template><!-- #pmtl-search-panel-template -->

        <template id="pmtl-dialog-visited-history-template">
            <form id="pmtl-visited-history-form" class="row gy-3 mb-3">
                <input id="pmtl-visited-place-id" type="hidden" name="placeId">
                <div class="col-sm-5">
                    <div class="form-floating">
                        <select id="pmtl-visited-history-year" class="form-select" name="year" aria-label="Select year visited"></select>
                        <label for="pmtl-visited-history-year">Year</label>
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="form-floating">
                        <select id="pmtl-visited-history-month" class="form-select" name="month" aria-label="Select month visited">
                            <option value="">All</option>
                            <?php
                            for ($i = 1; $i <= 12; ++$i) {
                                echo '<option value="' . $i . '">' . sprintf('%02d', $i) . '</option>';
                            }// endfor;
                            unset($i);
                            ?> 
                        </select>
                        <label for="pmtl-visited-history-month">Month</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
            <h5>Visited history</h5>
            <div id="pmtl-visited-history-dates-placeholder"></div>
        </template><!-- #pmtl-dialog-visited-history-template -->

        <script>
            const appBasePath = '<?php echo $Url->getAppBasePath(); ?>';
            let IndexJSObject = {
                /**
                 * @type {Object} The AJAX get summary result object. Usually it stored `recordDates.sinceDate`, `recordDates.latestDate` properties inside. Item was set from index.js -> `#ajaxGetSummary()`.
                 */
                'ajaxGetSummary': {},

                /**
                 * @type {float[]} For use as default map center.
                 */
                'defaultMapCenter': <?php 
                if (!defined('DEFAULT_MAP_CENTER')) {
                    define('DEFAULT_MAP_CENTER', [13.351245, 101.466092]);
                }
                echo '[' . DEFAULT_MAP_CENTER[0] . ', ' . DEFAULT_MAP_CENTER[1] . ']';
                ?>,

                /**
                 * @type {Boolean} For check that default maps was laded or not. Default is `false` or not loaded.
                 */
                'defaultMapsLoaded': false,

                /**
                 * @type {String|false} For store selected date that was loaded with AJAX timeline data. By default it was marked as `false` or means data was not loaded yet. Item was set from timeline-panel.js -> `#ajaxGetTimelineData()`.
                 */
                'loadSelectedDate': false,

                /**
                 * @type {Number|null} For set selected year from summary date on the navbar item.
                 */
                'summaryDateSelectedYear': null,
            };
        </script>
<?php
$customHTMLFoot = '<script src="' . $Assets->assetUrl('/assets/vendor/leaflet/leaflet.js', ['v' => '1.9.4']) . '"></script>
<script src="' . $Assets->assetUrl('assets/js/libraries/ajax.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/libraries/mapsUtil.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/libraries/utils.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/libraries/maps/libMapsLeaflet.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index/dialog-element.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index/listing-panel.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index/timeline-panel.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index/search-panel.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index/visited-history.js') . '"></script>
<script src="' . $Assets->assetUrl('assets/js/index.js') . '"></script>';
include 'HTTP/common/html-foot.php';
unset($customHTMLFoot, $customHTMLHead);
unset($Assets, $htmlTitle, $Url);
