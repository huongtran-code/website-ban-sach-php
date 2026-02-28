<?php
// Redirect to admin-authors.php with add tab
header("Location: admin-authors.php?tab=add" . (isset($_GET['error']) ? '&error=' . urlencode($_GET['error']) : '') . (isset($_GET['success']) ? '&success=' . urlencode($_GET['success']) : ''));
exit;
?>
