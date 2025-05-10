<?php
/** @var \PMTL\Libraries\Url $Url */
/** @var \PMTL\Libraries\Assets $Assets */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php 
        if (isset($htmlTitle) && is_scalar($htmlTitle)) {
            echo $htmlTitle;
        } else {
            echo 'Personal maps timeline.';
        } 
        ?></title>
        <!-- PMTL - Personal maps timeline. -->

        <link rel="stylesheet" href="<?php echo $Assets->assetUrl('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo $Assets->assetUrl('assets/vendor/fontawesome/css/all.min.css'); ?>">
        <?php
        if (isset($customHTMLHead) && is_scalar($customHTMLHead)) {
            echo $customHTMLHead;
        }
        ?> 
    </head>
    <body>
