<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

include "db_conn.php";
include "php/func-book.php";
include "php/func-rental.php";
include "php/func-settings.php";

$filter = $_GET['filter'] ?? 'all';
$rentals = ($filter == 'all') ? get_all_rentals($conn) : get_all_rentals($conn, $filter);

// Xử lý quá hạn tự động
if (isset($_GET['process_overdue'])) {
    $results = process_overdue_rentals($conn);
    $msg = "Đã xử lý: {$results['extended']} gia hạn, {$results['expired']} hết hạn";
    if (count($results['warned']) > 0) {
        $msg .= ", " . count($results['warned']) . " cảnh báo";
    }
    header("Location: admin-rentals.php?success=" . urlencode($msg));
    exit;
}

$max_late = (int)get_setting($conn, 'rental_max_late', 3);
$auto_extend = get_setting($conn, 'rental_auto_extend', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thuê sách - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header admin-header">
        <div class="header-main">
            <div class="container">
                <a href="admin.php" class="logo">
                    <span class="logo-icon">⚙️</span>
                    <span>Quản trị viên</span>
                </a>
                <div class="header-actions">
                    <a href="index.php"><i class="fas fa-store"></i> Xem cửa hàng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <?php include "php/admin-nav.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book-reader text-primary me-2"></i>Quản lý thuê sách</h2>
            <div class="d-flex gap-2">
                <a href="?process_overdue=1" class="btn btn-warning" onclick="return confirm('Xử lý tất cả sách quá hạn?')">
                    <i class="fas fa-sync-alt me-1"></i>Xử lý quá hạn
                </a>
                <a href="admin-settings.php" class="btn btn-outline-secondary">
                    <i class="fas fa-cog me-1"></i>Cài đặt
                </a>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <h3 class="text-primary"><?=count(get_all_rentals($conn, 'active'))?></h3>
                        <small>Đang thuê</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h3 class="text-danger"><?=count(check_overdue_rentals($conn))?></h3>
                        <small>Quá hạn</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h3 class="text-success"><?=count(get_all_rentals($conn, 'returned'))?></h3>
                        <small>Đã trả</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h3 class="text-warning"><?=$auto_extend ? 'Bật' : 'Tắt'?></h3>
                        <small>Tự động gia hạn</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="mb-3">
            <div class="btn-group">
                <a href="?filter=all" class="btn btn-<?=$filter=='all'?'primary':'outline-primary'?>">Tất cả</a>
                <a href="?filter=active" class="btn btn-<?=$filter=='active'?'success':'outline-success'?>">Đang thuê</a>
                <a href="?filter=returned" class="btn btn-<?=$filter=='returned'?'secondary':'outline-secondary'?>">Đã trả</a>
                <a href="?filter=expired" class="btn btn-<?=$filter=='expired'?'danger':'outline-danger'?>">Hết hạn</a>
            </div>
        </div>

        <!-- Danh sách -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($rentals)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Không có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Sách</th>
                                    <th>Người thuê</th>
                                    <th>Thời gian</th>
                                    <th>Trạng thái</th>
                                    <th>Trễ hạn</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rentals as $i => $rental): 
                                    $is_overdue = $rental['status'] == 'active' && strtotime($rental['end_date']) < time();
                                    $days_left = ceil((strtotime($rental['end_date']) - time()) / 86400);
                                ?>
                                    <tr class="<?=$is_overdue ? 'table-danger' : ''?> <?=$rental['late_count'] >= $max_late ? 'table-warning' : ''?>">
                                        <td><?=$i + 1?></td>
                                        <td>
                                            <img src="uploads/cover/<?=$rental['cover']?>" width="40" class="me-2 rounded">
                                            <?=htmlspecialchars($rental['title'])?>
                                        </td>
                                        <td>
                                            <strong><?=htmlspecialchars($rental['full_name'])?></strong><br>
                                            <small class="text-muted"><?=htmlspecialchars($rental['email'])?></small>
                                        </td>
                                        <td>
                                            <?=date('d/m/Y', strtotime($rental['start_date']))?> - <?=date('d/m/Y', strtotime($rental['end_date']))?>
                                            <?php if ($rental['status'] == 'active'): ?>
                                                <br><small class="text-<?=$days_left <= 0 ? 'danger' : 'muted'?>">
                                                    <?=$days_left <= 0 ? 'Quá hạn '.abs($days_left).' ngày' : 'Còn '.$days_left.' ngày'?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($rental['status'] == 'active'): ?>
                                                <?php if ($is_overdue): ?>
                                                    <span class="badge bg-danger">Quá hạn</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Đang thuê</span>
                                                <?php endif; ?>
                                            <?php elseif ($rental['status'] == 'returned'): ?>
                                                <span class="badge bg-secondary">Đã trả</span>
                                            <?php elseif ($rental['status'] == 'expired'): ?>
                                                <span class="badge bg-danger">Hết hạn</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($rental['auto_extend']): ?>
                                                <br><small class="badge bg-info">Tự động gia hạn</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($rental['late_count'] >= $max_late): ?>
                                                <span class="badge bg-danger"><?=$rental['late_count']?> <i class="fas fa-exclamation-triangle"></i></span>
                                            <?php elseif ($rental['late_count'] > 0): ?>
                                                <span class="badge bg-warning text-dark"><?=$rental['late_count']?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($rental['status'] == 'active'): ?>
                                                <a href="php/admin-extend-rental.php?id=<?=$rental['id']?>" class="btn btn-sm btn-outline-primary" onclick="return confirm('Gia hạn thêm 7 ngày?')">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                                <a href="php/admin-return-rental.php?id=<?=$rental['id']?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Xác nhận trả sách?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

