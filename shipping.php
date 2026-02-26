<?php session_start(); include "db_conn.php"; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phương Thức Vận Chuyển - Nhà Sách Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "php/header.php"; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h4>
                    </div>
                    <div class="card-body">
                        <h5>Đối tác vận chuyển</h5>
                        <p>Chúng tôi hợp tác với các đơn vị vận chuyển uy tín:</p>
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-truck fa-3x text-danger mb-2"></i>
                                <h6>GHN</h6>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-truck fa-3x text-success mb-2"></i>
                                <h6>GHTK</h6>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-truck fa-3x text-primary mb-2"></i>
                                <h6>J&T Express</h6>
                            </div>
                        </div>
                        
                        <h5>Thời gian giao hàng</h5>
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Khu vực</th>
                                    <th>Thời gian</th>
                                    <th>Phí ship</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nội thành TP.HCM, Hà Nội</td>
                                    <td>1-2 ngày</td>
                                    <td>25.000đ</td>
                                </tr>
                                <tr>
                                    <td>Các tỉnh thành khác</td>
                                    <td>3-5 ngày</td>
                                    <td>30.000đ</td>
                                </tr>
                                <tr>
                                    <td>Vùng sâu vùng xa</td>
                                    <td>5-7 ngày</td>
                                    <td>35.000đ</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-gift me-2"></i>
                            <strong>Ưu đãi:</strong> Miễn phí vận chuyển cho đơn hàng từ 300.000đ trở lên!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "php/footer.php"; ?>
</body>
</html>
