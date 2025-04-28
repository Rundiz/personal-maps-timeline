<?php


if (strtolower(php_sapi_name()) === 'cli') {
    throw new \Exception('Please run this file via HTTP.');
    exit();
}


require 'config.php';
require 'vendor/autoload.php';


$Url = new \PMTL\Libraries\Url();
$htmlTitle = 'Personal maps help'; // customize html title for each page.
include 'HTTP/common/html-head.php';
?>
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <h1 class="display-3"><?php echo $htmlTitle; ?></h1>
                    <h3>Export Google Maps timeline</h3>
                    <p>Follow this instruction on <a href="https://support.google.com/maps/thread/264641290/export-full-location-timeline-data-in-json-or-similar-format-in-the-new-version-of-timeline?hl=en" target="google-support">Google support page</a>.</p>

                    <h3>Import Google Maps timeline to this app</h3>
                    <ol>
                        <li>Extract <strong>.json</strong> file into your folder.</li>
                        <li>Edit path to your folder in variable <code>$jsonFolder</code> in a file <strong><?php echo __DIR__ . DIRECTORY_SEPARATOR; ?>config.php</strong>.</li>
                        <li>Run a command <code>php &quot;<?php echo __DIR__ . DIRECTORY_SEPARATOR; ?>pmtl.php&quot; import-json-to-db</code>.</li>
                    </ol>
                    <h4>Clear the imported data on DB.</h4>
                    <p>
                        If you have to clear all data on DB before import, run a command
                        <code>php &quot;<?php echo __DIR__ . DIRECTORY_SEPARATOR; ?>pmtl.php&quot; clear-db</code>.
                    </p>
                    <h4>Retrieve the place name</h4>
                    <p>To retrieve the place name from Google Places, run a command <code>php &quot;<?php echo __DIR__ . DIRECTORY_SEPARATOR; ?>pmtl.php&quot; retrieve-place-detail --help</code> to read more info.</p>

                    <p>
                        <a href="./">Go back to Personal maps timeline page</a>
                        |
                        <a href="https://github.com/Rundiz/personal-maps-timeline" target="github">Visit me on GitHub</a>
                    </p>
                </div>
            </div>
        </div>
<?php
include 'HTTP/common/html-foot.php';
unset($htmlTitle, $Url);