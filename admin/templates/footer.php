<?php
// This is a good place for any closing body wrappers if needed
// For example: </div> <!-- close .admin-wrapper -->
?>

<!-- Common admin scripts can go here -->
<!-- Example: <script src="path/to/bootstrap.bundle.min.js"></script> -->
<script src="js/searchable_select.js"></script>
<script>
    // Initialize Feather Icons, as seen in manage_banners.php implicitly used by class names
    // and explicitly in add_banner.php (e.g. <span data-feather="plus-circle"></span>)
    // It's better to call this once after all content is loaded.
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
</script>
</body>
</html>
