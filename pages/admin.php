<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";
include MODELS_PATH . "func-author.php";
include MODELS_PATH . "func-category.php";
include MODELS_PATH . "func-user.php";
include MODELS_PATH . "func-transaction.php";
include MODELS_PATH . "func-order.php";

$books = get_all_books($conn);
$authors = get_all_author($conn);
$categories = get_all_categories($conn);
$users = get_all_users($conn);
$transactions = get_all_transactions($conn, 10);
$low_stock_books = get_low_stock_books($conn, 5);
$pending_orders = get_pending_orders($conn, 10);

// Get locked or overdue users
$sql_problem_users = "SELECT u.id, u.full_name, u.email, u.balance, u.is_banned, u.ban_reason, COUNT(r.id) as overdue_count 
                      FROM users u 
                      LEFT JOIN rentals r ON u.id = r.user_id AND r.status = 'active' AND r.end_date < NOW()
                      WHERE u.is_banned = 1 OR r.id IS NOT NULL 
                      GROUP BY u.id
                      ORDER BY u.is_banned DESC, overdue_count DESC";
$stmt = $conn->prepare($sql_problem_users);
$stmt->execute();
$problem_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_books = get_total_books_count($conn);
$total_stock = get_total_stock($conn);
$total_revenue = get_total_revenue($conn);
$total_expense = get_total_expense($conn);
$total_deposits = get_total_deposits($conn);
$total_users = $users != 0 ? count($users) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        /* Professional Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card i {
            opacity: 0.9;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Financial Cards */
        .financial-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            background: white;
        }
        
        .financial-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .financial-card .card-body {
            padding: 1.75rem;
        }
        
        .financial-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .financial-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        /* Table Cards */
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            background: white;
        }
        
        .table-card .card-header {
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .table-card .table {
            margin: 0;
        }
        
        .table-card .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-card .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-card .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table-card .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Custom scrollbar */
        .scrollable-container {
            max-height: 500px;
            overflow-y: auto;
            position: relative;
        }
        
        .scrollable-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollable-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .scrollable-container::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #888 0%, #666 100%);
            border-radius: 4px;
        }
        
        .scrollable-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #666 0%, #555 100%);
        }
        
        .scrollable-container-sm {
            max-height: 400px;
            overflow-y: auto;
            position: relative;
        }
        
        .scrollable-container-sm::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollable-container-sm::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .scrollable-container-sm::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #888 0%, #666 100%);
            border-radius: 4px;
        }
        
        .scrollable-container-sm::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #666 0%, #555 100%);
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background: #f8f9fa !important;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Badge improvements */
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        /* Button improvements */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .btn-sm:hover {
            transform: scale(1.05);
        }
        
        /* Container improvements */
        .container {
            max-width: 1400px;
        }
        
        /* Alert improvements */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Modern Navigation Menu */
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 0;
        }
        
        .admin-nav .container {
            padding: 0;
        }
        
        .admin-nav ul {
            list-style: none;
            margin: 0;
            padding: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        
        .admin-nav li {
            margin: 0;
        }
        
        .admin-nav li a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .admin-nav li a i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
        }
        
        .admin-nav li a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .admin-nav li a.active {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-weight: 600;
        }
        
        .admin-nav li a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #fff;
            border-radius: 0 3px 3px 0;
        }
        
        /* Header improvements */
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header .logo {
            color: #fff;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .admin-header .header-actions a {
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        
        .admin-header .header-actions a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
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

    <?php include VIEWS_PATH . "admin-nav.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>


        <!-- Problem Users Warning (Locked/Overdue) -->
        <?php if (!empty($problem_users)): ?>
            <div class="alert alert-danger border-danger mb-4" style="border-left: 4px solid #dc3545;">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <i class="fas fa-user-lock fa-2x me-3 text-danger"></i>
                        <div>
                            <h5 class="mb-1"><strong>Cảnh báo: Tài khoản có vấn đề!</strong></h5>
                            <p class="mb-0">Có <strong><?=count($problem_users)?></strong> khách hàng bị khóa hoặc có sách quá hạn.</p>
                        </div>
                    </div>
                    <a href="admin-users.php" class="btn btn-danger">
                        <i class="fas fa-users-cog me-1"></i>Quản lý người dùng
                    </a>
                </div>
                <div class="mt-3">
                    <div class="table-responsive bg-white rounded border">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Khách hàng</th>
                                    <th>Trạng thái</th>
                                    <th>Sách quá hạn</th>
                                    <th>Số dư</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 0; foreach ($problem_users as $u): if ($count++ >= 5) break; ?>
                                    <tr>
                                        <td>
                                            <strong><?=htmlspecialchars($u['full_name'])?></strong><br>
                                            <small class="text-muted"><?=htmlspecialchars($u['email'])?></small>
                                        </td>
                                        <td>
                                            <?php if ($u['is_banned']): ?>
                                                <span class="badge bg-danger">Đang bị khóa</span>
                                                <div class="small text-danger mt-1"><?=htmlspecialchars($u['ban_reason'] ?? '')?></div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Warning</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['overdue_count'] > 0): ?>
                                                <span class="badge bg-danger"><?=$u['overdue_count']?> sách</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?= $u['balance'] < 0 ? 'text-danger' : '' ?>">
                                            <?=number_format($u['balance'], 0, ',', '.')?>đ
                                        </td>
                                        <td>
                                            <a href="edit-user.php?id=<?=$u['id']?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($problem_users) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">Và <?=count($problem_users) - 5?> tài khoản khác...</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Low Stock Warning -->
        <?php if ($low_stock_books != 0 && count($low_stock_books) > 0): ?>
            <div class="alert alert-warning border-warning mb-4" style="border-left: 4px solid #ffc107;">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                        <div>
                            <h5 class="mb-1"><strong>Cảnh báo: Sách sắp hết hàng!</strong></h5>
                            <p class="mb-0">Có <strong><?=count($low_stock_books)?></strong> đầu sách có số lượng dưới 5. Vui lòng bổ sung hàng.</p>
                        </div>
                    </div>
                    <a href="admin-books.php" class="btn btn-warning">
                        <i class="fas fa-arrow-right me-1"></i>Đi đến quản lý sách
                    </a>
                </div>
                <div class="mt-3">
                    <div class="row g-2">
                        <?php $count = 0; foreach ($low_stock_books as $book): if ($count++ >= 5) break; ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-white rounded border">
                                    <img src="../storage/uploads/cover/<?=$book['cover']?>" width="40" height="50" class="rounded me-2" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/40x50'">
                                    <div class="flex-grow-1">
                                        <strong class="d-block"><?=htmlspecialchars($book['title'])?></strong>
                                        <small class="text-muted">Số lượng: <span class="badge bg-danger"><?=$book['stock']?></span></small>
                                    </div>
                                    <a href="edit-book.php?id=<?=$book['id']?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($low_stock_books) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">Và <?=count($low_stock_books) - 5?> sách khác...</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pending Orders Warning -->
        <?php if ($pending_orders != 0 && count($pending_orders) > 0): ?>
            <div class="alert alert-info border-info mb-4" style="border-left: 4px solid #0dcaf0;">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <i class="fas fa-clock fa-2x me-3 text-info"></i>
                        <div>
                            <h5 class="mb-1"><strong>Đơn hàng đang đợi duyệt!</strong></h5>
                            <p class="mb-0">Có <strong><?=count($pending_orders)?></strong> đơn hàng đang chờ xử lý. Vui lòng kiểm tra và duyệt đơn.</p>
                        </div>
                    </div>
                    <a href="admin-orders.php?status=pending" class="btn btn-info">
                        <i class="fas fa-arrow-right me-1"></i>Đi đến quản lý đơn hàng
                    </a>
                </div>
                <div class="mt-3">
                    <div class="row g-2">
                        <?php $count = 0; foreach ($pending_orders as $order): if ($count++ >= 5) break; ?>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2 bg-white rounded border">
                                    <div class="me-3">
                                        <i class="fas fa-shopping-bag fa-2x text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong class="d-block">Đơn hàng #<?=$order['id']?>
                                            <?php if ($order['status'] == 'pending_cod'): ?>
                                                <span class="badge bg-warning ms-2">COD</span>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="text-muted d-block"><?=htmlspecialchars($order['full_name'] ?? 'N/A')?></small>
                                        <small class="text-muted">Tổng tiền: <span class="badge bg-info"><?=number_format($order['total_amount'], 0, ',', '.')?>đ</span></small>
                                        <br><small class="text-muted"><?=date('d/m/Y H:i', strtotime($order['created_at']))?></small>
                                    </div>
                                    <a href="admin-order-detail.php?id=<?=$order['id']?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($pending_orders) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">Và <?=count($pending_orders) - 5?> đơn hàng khác...</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4 g-3">
            <div class="col-md-3 col-sm-6">
                <div class="card text-white stat-card h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3" style="opacity: 0.9;">
                            <i class="fas fa-book fa-3x"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?=$total_books?></h3>
                            <p class="mb-0">Đầu sách</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white stat-card h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3" style="opacity: 0.9;">
                            <i class="fas fa-warehouse fa-3x"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?=number_format($total_stock)?></h3>
                            <p class="mb-0">Tổng kho</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white stat-card h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3" style="opacity: 0.9;">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?=$total_users?></h3>
                            <p class="mb-0">Khách hàng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white stat-card h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3" style="opacity: 0.9;">
                            <i class="fas fa-folder fa-3x"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?=$categories != 0 ? count($categories) : 0?></h3>
                            <p class="mb-0">Danh mục</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Stats -->
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card financial-card h-100 border-0">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-arrow-up text-success"></i>
                        </div>
                        <h4 class="text-success mb-2"><?=format_price($total_revenue)?></h4>
                        <p class="mb-0 text-muted small">Tổng doanh thu</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card financial-card h-100 border-0">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-arrow-down text-danger"></i>
                        </div>
                        <h4 class="text-danger mb-2"><?=format_price($total_expense)?></h4>
                        <p class="mb-0 text-muted small">Tổng chi phí</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card financial-card h-100 border-0">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-wallet text-primary"></i>
                        </div>
                        <h4 class="text-primary mb-2"><?=format_price($total_deposits)?></h4>
                        <p class="mb-0 text-muted small">Tổng nạp tiền</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Recent Transactions -->
            <div class="col-lg-6">
                <div class="card table-card h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Giao dịch gần đây</h5>
                        <a href="admin-transactions.php" class="btn btn-light btn-sm"><i class="fas fa-external-link-alt me-1"></i>Xem tất cả</a>
                    </div>
                    <div class="card-body p-0 scrollable-container-sm">
                        <?php if ($transactions == 0): ?>
                            <div class="text-center py-4"><p class="text-muted mb-0">Chưa có giao dịch</p></div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td>
                                                <?php if ($t['type'] == 'revenue'): ?>
                                                    <span class="badge bg-success">Thu</span>
                                                <?php elseif ($t['type'] == 'expense'): ?>
                                                    <span class="badge bg-danger">Chi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Nạp</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?=htmlspecialchars($t['description'] ?? 'N/A')?></td>
                                            <td class="text-end">
                                                <strong class="<?=$t['type'] == 'expense' ? 'text-danger' : 'text-success'?>">
                                                    <?=$t['type'] == 'expense' ? '-' : '+'?><?=format_price($t['amount'])?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-lg-6">
                <div class="card table-card h-100">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Khách hàng mới</h5>
                        <a href="admin-users.php" class="btn btn-light btn-sm"><i class="fas fa-external-link-alt me-1"></i>Xem tất cả</a>
                    </div>
                    <div class="card-body p-0 scrollable-container-sm">
                        <?php if ($users == 0): ?>
                            <div class="text-center py-4"><p class="text-muted mb-0">Chưa có khách hàng</p></div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <?php $count = 0; foreach ($users as $u): if ($count++ >= 5) break; ?>
                                        <tr>
                                            <td><i class="fas fa-user-circle text-muted me-2"></i><?=htmlspecialchars($u['full_name'])?></td>
                                            <td><?=htmlspecialchars($u['email'])?></td>
                                            <td class="text-end"><span class="badge bg-info"><?=format_price($u['balance'])?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

