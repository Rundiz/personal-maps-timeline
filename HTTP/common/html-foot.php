<?php
/** @var \PMTL\Libraries\Url $Url */
?>
        <script src="<?php echo $Url->getAppBasePath(); ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <?php
        if (isset($customHTMLFoot) && is_scalar($customHTMLFoot)) {
            echo $customHTMLFoot;
        }
        ?> 
    </body>
</html>
