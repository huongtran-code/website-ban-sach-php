<?php  
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

include "db_conn.php";
include "php/func-user.php";
include "php/func-book.php";

$users = get_all_users($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users text-primary"></i> Quản lý người dùng</h2>
            <div class="d-flex gap-2">
                <div class="input-group" style="max-width: 250px;">
                    <input type="text" class="form-control" id="searchUsers" placeholder="Tìm kiếm người dùng...">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                </div>
                <a href="add-user.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Thêm người dùng
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDepositModal">
                    <i class="fas fa-plus-circle me-1"></i>Nạp tiền
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <?php if ($users == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có người dùng nào</p>
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Số dư</th>
                                <th>Hạng thành viên</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0; foreach ($users as $u): $i++; ?>
                                <tr>
                                    <td><?=$i?></td>
                                    <td>
                                        <i class="fas fa-user-circle text-muted me-2"></i>
                                        <strong><?=htmlspecialchars($u['full_name'])?></strong>
                                    </td>
                                    <td><?=htmlspecialchars($u['email'])?></td>
                                    <td><?=htmlspecialchars($u['phone'] ?? 'N/A')?></td>
                                    <td>
                                        <span class="badge bg-success fs-6"><?=format_price($u['balance'])?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $membership_level = $u['membership_level'] ?? 'normal';
                                        $membership_name = get_membership_name($membership_level);
                                        $badge_class = 'bg-secondary';
                                        if ($membership_level == 'silver') $badge_class = 'bg-secondary';
                                        elseif ($membership_level == 'gold') $badge_class = 'bg-warning text-dark';
                                        elseif ($membership_level == 'diamond') $badge_class = 'bg-primary';
                                        ?>
                                        <span class="badge <?=$badge_class?>"><?=$membership_name?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['is_banned'])): ?>
                                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Bị khóa</span>
                                            <?php if (!empty($u['ban_reason'])): ?>
                                                <br><small class="text-muted"><?=htmlspecialchars($u['ban_reason'])?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?=date('d/m/Y', strtotime($u['created_at']))?></td>
                                    <td>
                                        <a href="edit-user.php?id=<?=$u['id']?>" class="btn btn-warning btn-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editMembershipModal<?=$u['id']?>" title="Sửa hạng">
                                            <i class="fas fa-crown"></i>
                                        </button>
                                        <a href="admin-deposit.php?user_id=<?=$u['id']?>" class="btn btn-success btn-sm" title="Nạp tiền">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <?php if (empty($u['is_banned'])): ?>
                                            <a href="php/toggle-user-ban.php?action=ban&id=<?=$u['id']?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Khóa tài khoản người dùng này? Họ sẽ không thể đăng nhập.')" title="Khóa tài khoản">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="php/toggle-user-ban.php?action=unban&id=<?=$u['id']?>" class="btn btn-outline-success btn-sm" onclick="return confirm('Mở khóa tài khoản người dùng này?')" title="Mở khóa tài khoản">
                                                <i class="fas fa-unlock"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="php/delete-user.php?id=<?=$u['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa người dùng này?')" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Deposit Modal -->
    <div class="modal fade" id="addDepositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="php/add-deposit.php" method="post">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nạp tiền cho người dùng</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Chọn người dùng</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Chọn --</option>
                                <?php if ($users != 0): foreach ($users as $u): ?>
                                    <option value="<?=$u['id']?>"><?=htmlspecialchars($u['full_name'])?> (<?=htmlspecialchars($u['email'])?>)</option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số tiền nạp</label>
                            <input type="number" name="amount" class="form-control" min="1000" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="description" class="form-control" placeholder="VD: Nạp qua MoMo">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Nạp tiền</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Membership Modal -->
    <?php if ($users != 0): ?>
        <?php foreach ($users as $u): ?>
        <div class="modal fade" id="editMembershipModal<?=$u['id']?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa hạng thành viên - <?=htmlspecialchars($u['full_name'])?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="php/edit-membership.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?=$u['id']?>">
                            <div class="mb-3">
                                <label class="form-label">Hạng thành viên hiện tại</label>
                                <div>
                                    <?php 
                                    $current_level = $u['membership_level'] ?? 'normal';
                                    $current_name = get_membership_name($current_level);
                                    ?>
                                    <span class="badge bg-secondary fs-6"><?=$current_name?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền đã mua</label>
                                <div>
                                    <strong><?=format_price($u['total_spent'] ?? 0)?></strong>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chọn hạng thành viên mới</label>
                                <select name="membership_level" class="form-select" required>
                                    <option value="normal" <?=($current_level == 'normal') ? 'selected' : ''?>>Thường</option>
                                    <option value="silver" <?=($current_level == 'silver') ? 'selected' : ''?>>Bạc (3% giảm giá)</option>
                                    <option value="gold" <?=($current_level == 'gold') ? 'selected' : ''?>>Vàng (8% giảm giá)</option>
                                    <option value="diamond" <?=($current_level == 'diamond') ? 'selected' : ''?>>Kim cương (10% giảm giá)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tìm kiếm người dùng
        document.getElementById('searchUsers').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
