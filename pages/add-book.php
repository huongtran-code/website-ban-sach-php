<?php
// Redirect to admin-books.php with add tab
header("Location: admin-books.php?tab=add" . (isset($_GET['error']) ? '&error=' . urlencode($_GET['error']) : '') . (isset($_GET['success']) ? '&success=' . urlencode($_GET['success']) : ''));
exit;
?>
