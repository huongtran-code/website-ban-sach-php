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

$books = get_all_books($conn);
$authors = get_all_author($conn);
$categories = get_all_categories($conn);

// Xác định tab active
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sách - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
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
        
        .scrollable-container {
            max-height: 600px;
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
            border-bottom-color: #667eea;
            color: #667eea;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            background: transparent;
            border-bottom-color: #667eea;
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

    <?php include VIEWS_PATH . "admin-nav.php"; ?>

    <div class="container py-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($_GET['error'])?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($_GET['success'])?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book text-primary"></i> Quản lý sách</h2>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?=$active_tab == 'list' ? 'active' : ''?>" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                    <i class="fas fa-list me-1"></i>Danh sách sách
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?=$active_tab == 'add' ? 'active' : ''?>" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
                    <i class="fas fa-plus me-1"></i>Thêm sách mới
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content">
            <!-- List Tab -->
            <div class="tab-pane fade <?=$active_tab == 'list' ? 'show active' : ''?>" id="list" role="tabpanel">
                <div class="card table-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Danh sách sách</h5>
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control form-control-sm" id="searchBooks" placeholder="Tìm kiếm sách...">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <div class="card-body p-0 scrollable-container">
                        <?php if ($books == 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có sách nào</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead class="table-light sticky-header">
                                    <tr>
                                        <th width="50">#</th>
                                        <th width="80">Bìa</th>
                                        <th>Tên sách</th>
                                        <th>Tác giả</th>
                                        <th>Giá</th>
                                        <th>Kho</th>
                                        <th width="120">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; foreach ($books as $book): $i++; ?>
                                        <tr>
                                            <td><?=$i?></td>
                                            <td><img src="../storage/uploads/cover/<?=$book['cover']?>" width="50" class="rounded" onerror="this.src='https://via.placeholder.com/50x70'"></td>
                                            <td><strong><?=htmlspecialchars($book['title'])?></strong></td>
                                            <td>
                                                <?php 
                                                $author_name = "N/A";
                                                if ($authors != 0) {
                                                    foreach ($authors as $author) {
                                                        if ($author['id'] == $book['author_id']) { $author_name = $author['name']; break; }
                                                    }
                                                }
                                                echo htmlspecialchars($author_name);
                                                ?>
                                            </td>
                                            <td><?=format_price($book['price'] ?? 0)?></td>
                                            <td>
                                                <?php $stock = $book['stock'] ?? 0; ?>
                                                <?php if ($stock > 10): ?>
                                                    <span class="badge bg-success"><?=$stock?></span>
                                                <?php elseif ($stock > 0): ?>
                                                    <span class="badge bg-warning"><?=$stock?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Hết</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit-book.php?id=<?=$book['id']?>" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                                <a href="../app/controllers/delete-book.php?id=<?=$book['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sách này?')" title="Xóa"><i class="fas fa-trash"></i></a>
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
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm sách mới</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="../app/controllers/add-book.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-book me-1"></i>Tên sách</label>
                                        <input type="text" class="form-control" name="book_title" 
                                               value="<?=isset($_GET['title']) ? htmlspecialchars($_GET['title']) : ''?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-align-left me-1"></i>Mô tả</label>
                                        <textarea class="form-control" name="book_description" rows="3" required><?=isset($_GET['desc']) ? htmlspecialchars($_GET['desc']) : ''?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-user me-1"></i>Tác giả</label>
                                            <select name="book_author" class="form-select" required>
                                                <option value="">-- Chọn tác giả --</option>
                                                <?php if ($authors != 0): ?>
                                                    <?php foreach ($authors as $author): ?>
                                                        <option value="<?=$author['id']?>"><?=htmlspecialchars($author['name'])?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-folder me-1"></i>Danh mục</label>
                                            <select name="book_category" class="form-select" required>
                                                <option value="">-- Chọn danh mục --</option>
                                                <?php if ($categories != 0): ?>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?=$cat['id']?>"><?=htmlspecialchars($cat['name'])?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-image me-1"></i>Ảnh bìa</label>
                                            <input type="file" class="form-control" name="book_cover" accept="image/*" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-file-pdf me-1"></i>File sách (PDF)</label>
                                            <input type="file" class="form-control" name="file" accept=".pdf" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i>Giá tiền (VNĐ)</label>
                                            <input type="number" class="form-control" name="price" 
                                                   value="<?=isset($_GET['price']) ? htmlspecialchars($_GET['price']) : ''?>" 
                                                   min="0" step="1000" required>
                                            <small class="text-muted">Ví dụ: 50000</small>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><i class="fas fa-box me-1"></i>Số lượng</label>
                                            <input type="number" class="form-control" name="stock" 
                                                   value="<?=isset($_GET['stock']) ? htmlspecialchars($_GET['stock']) : '10'?>" 
                                                   min="0" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><i class="fas fa-percent me-1"></i>% Giảm giá</label>
                                            <input type="number" class="form-control" name="discount_percent" 
                                                   value="<?=isset($_GET['discount']) ? htmlspecialchars($_GET['discount']) : '0'?>" 
                                                   min="0" max="100" step="1">
                                            <small class="text-muted">Mặc định: 0 (để trống = 0)</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><i class="fas fa-undo-alt me-1"></i>Số ngày cho phép hoàn trả</label>
                                            <input type="number" class="form-control" name="return_days"
                                                   value="<?=isset($_GET['return_days']) ? htmlspecialchars($_GET['return_days']) : '7'?>"
                                                   min="0" max="365" step="1">
                                            <small class="text-muted">Mặc định: 7 ngày (để trống = 7)</small>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Lưu sách
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
        // Tìm kiếm sách
        document.getElementById('searchBooks').addEventListener('input', function() {
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

