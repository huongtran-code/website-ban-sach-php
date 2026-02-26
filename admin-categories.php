<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

include "db_conn.php";
include "php/func-category.php";

$categories = get_all_categories($conn);

// Xác định tab active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
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
        
        .scrollable-container-sm {
            max-height: 600px;
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
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .btn-sm:hover {
            transform: scale(1.05);
        }
        
        .container {
            max-width: 1400px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            border-bottom-color: #28a745;
            color: #28a745;
        }
        
        .nav-tabs .nav-link.active {
            color: #28a745;
            background: transparent;
            border-bottom-color: #28a745;
            font-weight: 600;
        }
        
        .tab-content {
            margin-top: 0;
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
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>

        <div class="mb-4">
            <h2><i class="fas fa-folder text-success"></i> Quản lý danh mục</h2>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?=$active_tab == 'list' ? 'active' : ''?>" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                    <i class="fas fa-list me-1"></i>Danh sách danh mục
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?=$active_tab == 'add' ? 'active' : ''?>" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
                    <i class="fas fa-plus me-1"></i>Thêm danh mục mới
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content">
            <!-- List Tab -->
            <div class="tab-pane fade <?=$active_tab == 'list' ? 'show active' : ''?>" id="list" role="tabpanel">
                <div class="card table-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Danh mục</h5>
                        <div class="input-group" style="max-width: 250px;">
                            <input type="text" class="form-control form-control-sm" id="searchCategories" placeholder="Tìm kiếm danh mục...">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <div class="card-body p-0 scrollable-container-sm">
                        <?php if ($categories == 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có danh mục nào</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light sticky-header">
                                    <tr>
                                        <th>Tên danh mục</th>
                                        <th width="120" class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><i class="fas fa-folder-open text-success me-2"></i><?=htmlspecialchars($cat['name'])?></td>
                                            <td width="120" class="text-end">
                                                <a href="edit-category.php?id=<?=$cat['id']?>" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                                <a href="php/delete-category.php?id=<?=$cat['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa?')" title="Xóa"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add Tab -->
            <div class="tab-pane fade <?=$active_tab == 'add' ? 'show active' : ''?>" id="add" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-folder-plus me-2"></i>Thêm danh mục mới</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="php/add-category.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-tag me-1"></i>Tên danh mục</label>
                                        <input type="text" class="form-control" name="category_name" 
                                               value="<?=isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''?>" 
                                               placeholder="VD: Văn học, Kinh tế, Thiếu nhi..." required>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i>Lưu danh mục
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('list-tab').click()">
                                            <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tìm kiếm danh mục
        document.getElementById('searchCategories').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#list tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

