<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

include "db_conn.php";
include "php/func-transaction.php";
include "php/func-book.php";

$type_filter = $_GET['type'] ?? '';
$search_query = $_GET['search'] ?? '';
$transactions = get_all_transactions($conn, 200, $type_filter);
$total_revenue = get_total_revenue($conn);
$total_expense = get_total_expense($conn);
$total_deposits = get_total_deposits($conn);
$profit = $total_revenue - $total_expense;

// Date range for charts
$chart_period = $_GET['period'] ?? '7days';
$chart_to = $_GET['to_date'] ?? date('Y-m-d');
$chart_from = $_GET['from_date'] ?? '';

if ($chart_from === '') {
    switch ($chart_period) {
        case '30days': $chart_from = date('Y-m-d', strtotime('-29 days')); break;
        case 'this_month': $chart_from = date('Y-m-01'); break;
        case 'this_year': $chart_from = date('Y-01-01'); break;
        case 'custom': $chart_from = $chart_to; break;
        default: $chart_from = date('Y-m-d', strtotime('-6 days')); $chart_period = '7days'; break;
    }
}

// Validate dates
$start_dt = new DateTime($chart_from);
$end_dt = new DateTime($chart_to);
if ($start_dt > $end_dt) { $tmp = $chart_from; $chart_from = $chart_to; $chart_to = $tmp; $start_dt = new DateTime($chart_from); $end_dt = new DateTime($chart_to); }
$total_days = $start_dt->diff($end_dt)->days;

// Determine grouping: by day (<=31), by week (<=90), by month (>90)
$chart_data = [];
if ($total_days <= 31) {
    // Group by day
    for ($i = $total_days; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days", strtotime($chart_to)));
        $day_name = date('d/m', strtotime($date));
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type IN ('revenue', 'revenue_order', 'revenue_rental') THEN amount ELSE 0 END), 0) as revenue,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $day_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $chart_data[] = ['date' => $day_name, 'revenue' => (float)$day_data['revenue'], 'expense' => (float)$day_data['expense']];
    }
} elseif ($total_days <= 90) {
    // Group by week
    $current = clone $start_dt;
    while ($current <= $end_dt) {
        $week_start = $current->format('Y-m-d');
        $week_end_dt = clone $current;
        $week_end_dt->modify('+6 days');
        if ($week_end_dt > $end_dt) $week_end_dt = clone $end_dt;
        $week_end = $week_end_dt->format('Y-m-d');
        $label = $current->format('d/m');
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type IN ('revenue', 'revenue_order', 'revenue_rental') THEN amount ELSE 0 END), 0) as revenue,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->execute([$week_start, $week_end]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $chart_data[] = ['date' => $label, 'revenue' => (float)$data['revenue'], 'expense' => (float)$data['expense']];
        $current->modify('+7 days');
    }
} else {
    // Group by month
    $current = clone $start_dt;
    $current->modify('first day of this month');
    while ($current <= $end_dt) {
        $month_start = $current->format('Y-m-01');
        $month_end = $current->format('Y-m-t');
        $label = $current->format('m/Y');
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type IN ('revenue', 'revenue_order', 'revenue_rental') THEN amount ELSE 0 END), 0) as revenue,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
            FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->execute([$month_start, $month_end]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $chart_data[] = ['date' => $label, 'revenue' => (float)$data['revenue'], 'expense' => (float)$data['expense']];
        $current->modify('+1 month');
    }
}

// Chart period label
$period_label = 'Từ ' . date('d/m/Y', strtotime($chart_from)) . ' đến ' . date('d/m/Y', strtotime($chart_to));

// Lấy dữ liệu theo loại giao dịch chi tiết (filtered by date range) 
$stmt_summary = $conn->prepare("SELECT type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
    FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY type ORDER BY total DESC");
$stmt_summary->execute([$chart_from, $chart_to]);
$transaction_summary = $stmt_summary->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng từng nhóm cho pie chart
$pie_data = [];
$pie_labels = [];
$pie_colors = [];
foreach ($transaction_summary as $row) {
    $info = get_transaction_type_info($row['type']);
    $pie_labels[] = $info['label'];
    $pie_data[] = (float)$row['total'];
    // Map Bootstrap color to hex
    $color_map = [
        'primary' => '#0d6efd', 'success' => '#198754', 'danger' => '#dc3545',
        'warning' => '#ffc107', 'info' => '#0dcaf0', 'secondary' => '#6c757d',
        'dark' => '#212529'
    ];
    $pie_colors[] = $color_map[$info['color']] ?? '#6c757d';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .chart-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            background: white;
        }
        .chart-card .card-header {
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        /* Chart Filter Toggles */
        .chart-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .chart-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            border: 2px solid;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .chart-filter-btn .filter-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .chart-filter-btn.active {
            color: white;
        }
        .chart-filter-btn:not(.active) {
            background: white;
            opacity: 0.6;
        }
        .chart-filter-btn:not(.active) .filter-dot {
            opacity: 0.4;
        }
        .chart-filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .chart-filter-actions {
            display: flex;
            gap: 6px;
            margin-left: auto;
        }
        .chart-filter-actions .btn {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 12px;
        }
        /* Date Range Filter */
        .date-filter-bar {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .date-filter-bar .period-btns {
            display: flex;
            gap: 4px;
        }
        .date-filter-bar .period-btns .btn {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 16px;
        }
        .date-filter-bar .date-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .date-filter-bar .date-inputs input {
            font-size: 13px;
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .date-filter-bar .date-inputs .separator {
            color: #999;
            font-size: 13px;
        }
        /* Search bar */
        .search-bar {
            position: relative;
        }
        .search-bar input {
            font-size: 13px;
            padding: 6px 12px 6px 32px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            width: 220px;
            transition: all 0.3s;
        }
        .search-bar input::placeholder { color: rgba(255,255,255,0.6); }
        .search-bar input:focus {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            outline: none;
            width: 280px;
        }
        .search-bar i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 13px;
        }
        .search-count {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            margin-left: 4px;
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

    <?php include "php/admin-nav.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>
        
        <h2 class="mb-4"><i class="fas fa-chart-bar text-primary"></i> Thống kê</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <a href="#best-selling-books" class="text-decoration-none">
                    <div class="card chart-card" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body text-center" onmouseover="this.parentElement.style.transform='scale(1.05)'" onmouseout="this.parentElement.style.transform='scale(1)'">
                            <i class="fas fa-book fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0">Sách bán nhiều nhất</h5>
                            <p class="text-muted small mb-0">Top 10 sản phẩm</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="#best-selling-authors" class="text-decoration-none">
                    <div class="card chart-card" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body text-center" onmouseover="this.parentElement.style.transform='scale(1.05)'" onmouseout="this.parentElement.style.transform='scale(1)'">
                            <i class="fas fa-user-edit fa-2x text-success mb-2"></i>
                            <h5 class="mb-0">Tác giả bán chạy</h5>
                            <p class="text-muted small mb-0">Top 10 tác giả</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="#top-customers" class="text-decoration-none">
                    <div class="card chart-card" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body text-center" onmouseover="this.parentElement.style.transform='scale(1.05)'" onmouseout="this.parentElement.style.transform='scale(1)'">
                            <i class="fas fa-users fa-2x text-warning mb-2"></i>
                            <h5 class="mb-0">Khách hàng VIP</h5>
                            <p class="text-muted small mb-0">Top 10 khách hàng</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="#revenue-expense" class="text-decoration-none">
                    <div class="card chart-card" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body text-center" onmouseover="this.parentElement.style.transform='scale(1.05)'" onmouseout="this.parentElement.style.transform='scale(1)'">
                            <i class="fas fa-money-bill-wave fa-2x text-danger mb-2"></i>
                            <h5 class="mb-0">Thu chi</h5>
                            <p class="text-muted small mb-0">Tổng quan tài chính</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Best Selling Books -->
        <?php
        $stmt = $conn->prepare("SELECT b.id, b.title, b.cover, 
                                       COALESCE(SUM(oi.quantity), 0) as total_sold,
                                       COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue
                                FROM books b
                                LEFT JOIN order_items oi ON b.id = oi.book_id
                                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                                GROUP BY b.id, b.title, b.cover
                                ORDER BY total_sold DESC
                                LIMIT 10");
        $stmt->execute();
        $best_selling_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="row mb-4" id="best-selling-books">
            <div class="col-md-6">
                <div class="card chart-card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-book me-2"></i>Sách bán nhiều nhất
                    </div>
                    <div class="card-body">
                        <?php if (count($best_selling_books) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Sách</th>
                                            <th>Số lượng</th>
                                            <th>Doanh thu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0; foreach ($best_selling_books as $book): $i++; ?>
                                        <tr>
                                            <td><?=$i?></td>
                                            <td>
                                                <img src="uploads/cover/<?=$book['cover']?>" width="40" height="50" class="me-2" style="object-fit: cover;">
                                                <small><?=htmlspecialchars($book['title'])?></small>
                                            </td>
                                            <td><span class="badge bg-success"><?=$book['total_sold']?></span></td>
                                            <td><?=format_price($book['total_revenue'])?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Chưa có dữ liệu</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Best Selling Authors -->
            <?php
            $stmt = $conn->prepare("SELECT a.id, a.name,
                                           COALESCE(SUM(oi.quantity), 0) as total_sold,
                                           COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue
                                    FROM authors a
                                    LEFT JOIN books b ON a.id = b.author_id
                                    LEFT JOIN order_items oi ON b.id = oi.book_id
                                    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                                    GROUP BY a.id, a.name
                                    ORDER BY total_sold DESC
                                    LIMIT 10");
            $stmt->execute();
            $best_selling_authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <div class="col-md-6" id="best-selling-authors">
                <div class="card chart-card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-user-edit me-2"></i>Tác giả bán chạy nhất
                    </div>
                    <div class="card-body">
                        <?php if (count($best_selling_authors) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tác giả</th>
                                            <th>Số lượng</th>
                                            <th>Doanh thu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0; foreach ($best_selling_authors as $author): $i++; ?>
                                        <tr>
                                            <td><?=$i?></td>
                                            <td><strong><?=htmlspecialchars($author['name'])?></strong></td>
                                            <td><span class="badge bg-success"><?=$author['total_sold']?></span></td>
                                            <td><?=format_price($author['total_revenue'])?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Chưa có dữ liệu</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Customers -->
        <?php
        $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email,
                                       COUNT(DISTINCT o.id) as total_orders,
                                       COALESCE(SUM(o.total_amount), 0) as total_spent
                                FROM users u
                                LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
                                GROUP BY u.id, u.full_name, u.email
                                HAVING total_orders > 0
                                ORDER BY total_spent DESC
                                LIMIT 10");
        $stmt->execute();
        $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="row mb-4" id="top-customers">
            <div class="col-md-12">
                <div class="card chart-card">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-users me-2"></i>Khách hàng mua nhiều nhất
                    </div>
                    <div class="card-body">
                        <?php if (count($top_customers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Khách hàng</th>
                                            <th>Email</th>
                                            <th>Số đơn hàng</th>
                                            <th>Tổng tiền đã mua</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0; foreach ($top_customers as $customer): $i++; ?>
                                        <tr>
                                            <td><?=$i?></td>
                                            <td><strong><?=htmlspecialchars($customer['full_name'])?></strong></td>
                                            <td><?=htmlspecialchars($customer['email'])?></td>
                                            <td><span class="badge bg-primary"><?=$customer['total_orders']?></span></td>
                                            <td><strong class="text-success"><?=format_price($customer['total_spent'])?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Chưa có dữ liệu</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="my-4" id="revenue-expense">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-chart-line text-primary"></i> Thống kê thu chi</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-plus-circle me-1"></i>Thêm giao dịch
            </button>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-trend-up fa-2x text-success mb-2"></i>
                        <h4 class="text-success"><?=format_price($total_revenue)?></h4>
                        <p class="mb-0 text-muted">Tổng doanh thu</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-danger h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-trend-down fa-2x text-danger mb-2"></i>
                        <h4 class="text-danger"><?=format_price($total_expense)?></h4>
                        <p class="mb-0 text-muted">Tổng chi phí</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-wallet fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary"><?=format_price($total_deposits)?></h4>
                        <p class="mb-0 text-muted">Tổng nạp tiền</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                        <h4 class="<?=$profit >= 0 ? 'text-success' : 'text-danger'?>"><?=format_price($profit)?></h4>
                        <p class="mb-0 text-muted">Lợi nhuận</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chi tiết theo hạng mục -->
        <div class="row mb-4">
            <?php foreach ($transaction_summary as $ts): 
                $ti = get_transaction_type_info($ts['type']);
                $is_active = ($type_filter === $ts['type']);
            ?>
            <div class="col-md-3 col-sm-4 col-6 mb-3">
                <a href="admin-transactions.php?type=<?=$ts['type']?>#revenue-expense" class="text-decoration-none">
                    <div class="card h-100<?=$is_active ? ' border-primary border-2 shadow' : ''?>" style="cursor:pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="card-body text-center py-3">
                            <i class="fas <?=$ti['icon']?> fa-lg text-<?=$ti['color']?> mb-1"></i>
                            <h6 class="mb-0"><?=format_price($ts['total'])?></h6>
                            <small class="text-muted"><?=$ti['label']?> (<?=$ts['count']?>)</small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Date Range Filter -->
        <div class="date-filter-bar" id="dateFilterBar">
            <div>
                <i class="fas fa-calendar-alt text-primary me-1"></i>
                <strong style="font-size: 14px;">Khoảng thời gian:</strong>
            </div>
            <div class="period-btns">
                <a href="admin-transactions.php?period=7days&type=<?=$type_filter?>#revenue-expense" class="btn <?=$chart_period === '7days' ? 'btn-primary' : 'btn-outline-primary'?>">7 ngày</a>
                <a href="admin-transactions.php?period=30days&type=<?=$type_filter?>#revenue-expense" class="btn <?=$chart_period === '30days' ? 'btn-primary' : 'btn-outline-primary'?>">30 ngày</a>
                <a href="admin-transactions.php?period=this_month&type=<?=$type_filter?>#revenue-expense" class="btn <?=$chart_period === 'this_month' ? 'btn-primary' : 'btn-outline-primary'?>">Tháng này</a>
                <a href="admin-transactions.php?period=this_year&type=<?=$type_filter?>#revenue-expense" class="btn <?=$chart_period === 'this_year' ? 'btn-primary' : 'btn-outline-primary'?>">Năm nay</a>
            </div>
            <div class="date-inputs">
                <input type="date" id="fromDate" value="<?=$chart_from?>">
                <span class="separator">→</span>
                <input type="date" id="toDate" value="<?=$chart_to?>">
                <button class="btn btn-sm btn-primary" onclick="applyDateFilter()" style="border-radius: 16px; font-size: 12px; padding: 5px 14px;">
                    <i class="fas fa-search me-1"></i>Xem
                </button>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4 g-3">
            <div class="col-lg-8">
                <div class="card chart-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Biểu đồ thu chi</h5>
                        <small class="opacity-75"><?=$period_label?></small>
                    </div>
                    <div class="chart-filters" id="lineChartFilters">
                        <span class="chart-filter-btn active" data-index="0" onclick="toggleLineDataset(this, 0)" style="border-color: rgb(40, 167, 69); background: rgb(40, 167, 69);">
                            <span class="filter-dot" style="background: white;"></span> Doanh thu
                        </span>
                        <span class="chart-filter-btn active" data-index="1" onclick="toggleLineDataset(this, 1)" style="border-color: rgb(220, 53, 69); background: rgb(220, 53, 69);">
                            <span class="filter-dot" style="background: white;"></span> Chi phí
                        </span>
                    </div>
                    <div class="card-body">
                        <canvas id="lineChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card chart-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Phân bổ theo hạng mục</h5>
                    </div>
                    <div class="chart-filters" id="pieChartFilters">
                        <?php foreach ($pie_labels as $idx => $label): ?>
                        <span class="chart-filter-btn active" data-index="<?=$idx?>" onclick="togglePieSegment(this, <?=$idx?>)" style="border-color: <?=$pie_colors[$idx]?>; background: <?=$pie_colors[$idx]?>;">
                            <span class="filter-dot" style="background: white;"></span> <?=$label?>
                        </span>
                        <?php endforeach; ?>
                        <div class="chart-filter-actions">
                            <button class="btn btn-outline-secondary" onclick="toggleAllPie(true)">Tất cả</button>
                            <button class="btn btn-outline-secondary" onclick="toggleAllPie(false)">Bỏ chọn</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử giao dịch</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Tìm kiếm -->
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="transactionSearch" placeholder="Tìm kiếm giao dịch..." oninput="filterTransactions()">
                    </div>
                    <span class="search-count" id="searchCount"></span>
                    <!-- Nhóm lọc nhanh -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="admin-transactions.php?period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" class="btn <?=$type_filter === '' ? 'btn-light' : 'btn-outline-light'?>">Tất cả</a>
                        <a href="admin-transactions.php?type=group_revenue&period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" class="btn <?=$type_filter === 'group_revenue' ? 'btn-light' : 'btn-outline-light'?>">Doanh thu</a>
                        <a href="admin-transactions.php?type=group_rental&period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" class="btn <?=$type_filter === 'group_rental' ? 'btn-light' : 'btn-outline-light'?>">Thuê sách</a>
                        <a href="admin-transactions.php?type=deposit&period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" class="btn <?=$type_filter === 'deposit' ? 'btn-light' : 'btn-outline-light'?>">Nạp tiền</a>
                        <a href="admin-transactions.php?type=expense&period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" class="btn <?=$type_filter === 'expense' ? 'btn-light' : 'btn-outline-light'?>">Chi phí</a>
                    </div>
                    <!-- Dropdown chi tiết -->
                    <select class="form-select form-select-sm bg-dark text-white border-light" style="width: auto; min-width: 160px;" onchange="if(this.value) window.location.href=this.value">
                        <option value="admin-transactions.php?period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" <?=$type_filter === '' ? 'selected' : ''?>>-- Lọc chi tiết --</option>
                        <?php foreach (get_all_transaction_types() as $t_type): 
                            $t_info = get_transaction_type_info($t_type);
                        ?>
                            <option value="admin-transactions.php?type=<?=$t_type?>&period=<?=$chart_period?>&from_date=<?=$chart_from?>&to_date=<?=$chart_to?>#revenue-expense" <?=$type_filter === $t_type ? 'selected' : ''?>>
                                <?=$t_info['label']?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Export Excel -->
                    <button class="btn btn-sm btn-success" onclick="exportExcel()" title="Xuất file Excel">
                        <i class="fas fa-file-excel me-1"></i>Xuất Excel
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($transactions == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có giao dịch nào</p>
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0" id="transactionsTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Loại</th>
                                <th>Người dùng</th>
                                <th>Mô tả</th>
                                <th>Số tiền</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0; foreach ($transactions as $t): $i++; 
                                $ti = get_transaction_type_info($t['type']);
                            ?>
                                <tr>
                                    <td><?=$i?></td>
                                    <td>
                                        <span class="badge bg-<?=$ti['color']?>">
                                            <i class="fas <?=$ti['icon']?> me-1"></i><?=$ti['label']?>
                                        </span>
                                    </td>
                                    <td><?=htmlspecialchars($t['user_name'] ?? 'Hệ thống')?></td>
                                    <td><?=htmlspecialchars($t['description'] ?? 'N/A')?></td>
                                    <td>
                                        <strong class="<?=is_expense_type($t['type']) ? 'text-danger' : 'text-success'?>">
                                            <?=$ti['direction']?><?=format_price($t['amount'])?>
                                        </strong>
                                    </td>
                                    <td><?=date('d/m/Y H:i', strtotime($t['created_at']))?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Export loading overlay -->
    <div id="exportOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:none; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:12px; text-align:center;">
            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
            <p class="mb-0">Đang xuất file...</p>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="php/add-transaction.php" method="post">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Thêm giao dịch</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Loại giao dịch</label>
                            <select name="type" class="form-select" required>
                                <option value="revenue">Thu (Doanh thu)</option>
                                <option value="expense">Chi (Chi phí)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số tiền</label>
                            <input type="number" name="amount" class="form-control" min="1000" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <input type="text" name="description" class="form-control" placeholder="VD: Doanh thu bán sách tháng 1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Line Chart - 7 ngày gần nhất
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?=json_encode(array_column($chart_data, 'date'))?>,
                datasets: [{
                    label: 'Doanh thu',
                    data: <?=json_encode(array_column($chart_data, 'revenue'))?>,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Chi phí',
                    data: <?=json_encode(array_column($chart_data, 'expense'))?>,
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND',
                                    notation: 'compact'
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });

        // Toggle line chart datasets
        function toggleLineDataset(btn, index) {
            btn.classList.toggle('active');
            const isActive = btn.classList.contains('active');
            const color = lineChart.data.datasets[index].borderColor;
            
            if (isActive) {
                btn.style.background = color;
                btn.style.color = 'white';
            } else {
                btn.style.background = 'white';
                btn.style.color = color;
            }
            
            lineChart.data.datasets[index].hidden = !isActive;
            lineChart.update();
        }

        // Pie Chart - Phân bổ theo hạng mục
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const originalPieData = <?=json_encode($pie_data)?>;
        const originalPieLabels = <?=json_encode($pie_labels)?>;
        const originalPieColors = <?=json_encode($pie_colors)?>;
        const pieVisibility = new Array(originalPieData.length).fill(true);
        
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: [...originalPieLabels],
                datasets: [{
                    data: [...originalPieData],
                    backgroundColor: [...originalPieColors],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(value) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Toggle pie chart segments
        function togglePieSegment(btn, index) {
            btn.classList.toggle('active');
            const isActive = btn.classList.contains('active');
            const color = originalPieColors[index];
            
            if (isActive) {
                btn.style.background = color;
                btn.style.color = 'white';
            } else {
                btn.style.background = 'white';
                btn.style.color = color;
            }
            
            pieVisibility[index] = isActive;
            updatePieChart();
        }

        function toggleAllPie(show) {
            const btns = document.querySelectorAll('#pieChartFilters .chart-filter-btn');
            btns.forEach((btn, i) => {
                const idx = parseInt(btn.dataset.index);
                if (idx >= 0) {
                    pieVisibility[idx] = show;
                    const color = originalPieColors[idx];
                    if (show) {
                        btn.classList.add('active');
                        btn.style.background = color;
                        btn.style.color = 'white';
                    } else {
                        btn.classList.remove('active');
                        btn.style.background = 'white';
                        btn.style.color = color;
                    }
                }
            });
            updatePieChart();
        }

        function updatePieChart() {
            const newLabels = [];
            const newData = [];
            const newColors = [];
            
            for (let i = 0; i < originalPieData.length; i++) {
                if (pieVisibility[i]) {
                    newLabels.push(originalPieLabels[i]);
                    newData.push(originalPieData[i]);
                    newColors.push(originalPieColors[i]);
                }
            }
            
            pieChart.data.labels = newLabels;
            pieChart.data.datasets[0].data = newData;
            pieChart.data.datasets[0].backgroundColor = newColors;
            pieChart.update();
        }

        // Date filter
        function applyDateFilter() {
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            if (!from || !to) {
                alert('Vui lòng chọn ngày bắt đầu và kết thúc');
                return;
            }
            const type = '<?=$type_filter?>';
            let url = `admin-transactions.php?period=custom&from_date=${from}&to_date=${to}`;
            if (type) url += `&type=${type}`;
            url += '#revenue-expense';
            window.location.href = url;
        }

        // Vietnamese accent removal - manual mapping for reliability
        const vnMap = {
            'à':'a','á':'a','ả':'a','ã':'a','ạ':'a',
            'ă':'a','ằ':'a','ắ':'a','ẳ':'a','ẵ':'a','ặ':'a',
            'â':'a','ầ':'a','ấ':'a','ẩ':'a','ẫ':'a','ậ':'a',
            'è':'e','é':'e','ẻ':'e','ẽ':'e','ẹ':'e',
            'ê':'e','ề':'e','ế':'e','ể':'e','ễ':'e','ệ':'e',
            'ì':'i','í':'i','ỉ':'i','ĩ':'i','ị':'i',
            'ò':'o','ó':'o','ỏ':'o','õ':'o','ọ':'o',
            'ô':'o','ồ':'o','ố':'o','ổ':'o','ỗ':'o','ộ':'o',
            'ơ':'o','ờ':'o','ớ':'o','ở':'o','ỡ':'o','ợ':'o',
            'ù':'u','ú':'u','ủ':'u','ũ':'u','ụ':'u',
            'ư':'u','ừ':'u','ứ':'u','ử':'u','ữ':'u','ự':'u',
            'ỳ':'y','ý':'y','ỷ':'y','ỹ':'y','ỵ':'y',
            'đ':'d',
            'À':'A','Á':'A','Ả':'A','Ã':'A','Ạ':'A',
            'Ă':'A','Ằ':'A','Ắ':'A','Ẳ':'A','Ẵ':'A','Ặ':'A',
            'Â':'A','Ầ':'A','Ấ':'A','Ẩ':'A','Ẫ':'A','Ậ':'A',
            'È':'E','É':'E','Ẻ':'E','Ẽ':'E','Ẹ':'E',
            'Ê':'E','Ề':'E','Ế':'E','Ể':'E','Ễ':'E','Ệ':'E',
            'Ì':'I','Í':'I','Ỉ':'I','Ĩ':'I','Ị':'I',
            'Ò':'O','Ó':'O','Ỏ':'O','Õ':'O','Ọ':'O',
            'Ô':'O','Ồ':'O','Ố':'O','Ổ':'O','Ỗ':'O','Ộ':'O',
            'Ơ':'O','Ờ':'O','Ớ':'O','Ở':'O','Ỡ':'O','Ợ':'O',
            'Ù':'U','Ú':'U','Ủ':'U','Ũ':'U','Ụ':'U',
            'Ư':'U','Ừ':'U','Ứ':'U','Ử':'U','Ữ':'U','Ự':'U',
            'Ỳ':'Y','Ý':'Y','Ỷ':'Y','Ỹ':'Y','Ỵ':'Y',
            'Đ':'D'
        };
        
        function removeVnAccents(str) {
            let result = '';
            for (let i = 0; i < str.length; i++) {
                result += vnMap[str[i]] || str[i];
            }
            return result;
        }

        // Transaction search
        let searchTimer = null;
        function filterTransactions() {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(runSearch, 200);
        }

        function runSearch() {
            const input = document.getElementById('transactionSearch');
            if (!input) return;
            const rawQuery = input.value.trim();
            const query = removeVnAccents(rawQuery).toLowerCase();
            
            const table = document.getElementById('transactionsTable');
            if (!table) return;
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = tbody.getElementsByTagName('tr');
            let visible = 0;
            const total = rows.length;
            
            for (let r = 0; r < total; r++) {
                const row = rows[r];
                const cells = row.getElementsByTagName('td');
                let rowText = '';
                for (let c = 0; c < cells.length; c++) {
                    rowText += ' ' + cells[c].textContent;
                }
                const normalizedRow = removeVnAccents(rowText).toLowerCase();
                
                if (query === '' || normalizedRow.indexOf(query) !== -1) {
                    row.style.cssText = '';
                    visible++;
                    // Highlight
                    if (query !== '') {
                        for (let c = 0; c < cells.length; c++) {
                            doHighlight(cells[c], query, rawQuery);
                        }
                    } else {
                        for (let c = 0; c < cells.length; c++) {
                            clearHighlight(cells[c]);
                        }
                    }
                } else {
                    row.style.cssText = 'display:none !important';
                    for (let c = 0; c < cells.length; c++) {
                        clearHighlight(cells[c]);
                    }
                }
            }
            
            const countEl = document.getElementById('searchCount');
            if (query !== '') {
                countEl.textContent = '(' + visible + '/' + total + ')';
                countEl.style.color = visible > 0 ? 'rgba(255,255,255,0.8)' : '#ff6b6b';
            } else {
                countEl.textContent = '';
            }
        }

        function doHighlight(cell, normalizedQuery, rawQuery) {
            if (cell.querySelector('.badge')) return;
            
            if (!cell.hasAttribute('data-orig')) {
                cell.setAttribute('data-orig', cell.textContent);
            }
            const orig = cell.getAttribute('data-orig');
            const normalizedOrig = removeVnAccents(orig).toLowerCase();
            const idx = normalizedOrig.indexOf(normalizedQuery);
            
            if (idx === -1) {
                cell.innerHTML = escapeHtml(orig);
                return;
            }
            
            const before = orig.substring(0, idx);
            const match = orig.substring(idx, idx + normalizedQuery.length);
            const after = orig.substring(idx + normalizedQuery.length);
            
            cell.innerHTML = escapeHtml(before) + 
                '<mark style="background:#ffe066;padding:1px 3px;border-radius:3px;">' + 
                escapeHtml(match) + '</mark>' + escapeHtml(after);
        }

        function clearHighlight(cell) {
            if (cell.hasAttribute('data-orig')) {
                cell.textContent = cell.getAttribute('data-orig');
                cell.removeAttribute('data-orig');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Export Excel
        function exportExcel() {
            const type = '<?=$type_filter?>';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const search = document.getElementById('transactionSearch')?.value || '';
            
            let url = `php/export-transactions.php?`;
            const params = [];
            if (type) params.push(`type=${type}`);
            if (from) params.push(`from_date=${from}`);
            if (to) params.push(`to_date=${to}`);
            if (search) params.push(`search=${encodeURIComponent(search)}`);
            url += params.join('&');
            
            window.location.href = url;
        }

        // Enter key on date inputs
        document.getElementById('fromDate')?.addEventListener('keydown', e => { if(e.key === 'Enter') applyDateFilter(); });
        document.getElementById('toDate')?.addEventListener('keydown', e => { if(e.key === 'Enter') applyDateFilter(); });
    </script>
</body>
</html>
