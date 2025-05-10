<?php
/** @var \PMTL\Libraries\Url $Url */
/** @var \PMTL\Libraries\Assets $Assets */
?>
<script src="<?php echo $Assets->assetUrl('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
        <?php
        if (isset($customHTMLFoot) && is_scalar($customHTMLFoot)) {
            echo $customHTMLFoot;
        }
        ?> 
    </body>
</html>
