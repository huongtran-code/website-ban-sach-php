<?php
// Redirect to admin-categories.php with add tab
header("Location: admin-categories.php?tab=add" . (isset($_GET['error']) ? '&error=' . urlencode($_GET['error']) : '') . (isset($_GET['success']) ? '&success=' . urlencode($_GET['success']) : ''));
exit;
?>
