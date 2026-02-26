<?php
session_start();
include "../db_conn.php";
include "func-user.php";
include "func-rental.php";

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = get_user_by_email($conn, $email);

    if ($user && password_verify($password, $user['password'])) {
        if (!empty($user['is_banned'])) {
            // Check if it's an overdue ban
            $lock_status = check_overdue_lock_status($conn, $user['id']);
            if ($lock_status['status'] == 'locked') {
                 // Store data and redirect to unlock page
                 $_SESSION['locked_user_id'] = $user['id'];
                 $_SESSION['locked_reason'] = $lock_status['reason'];
                 $_SESSION['locked_amount'] = $lock_status['shortfall'];
                 $_SESSION['locked_penalty'] = $lock_status['penalty'];
                 $_SESSION['locked_balance'] = $lock_status['balance'];
                 
                 header("Location: ../locked.php");
                 exit;
            }
            
            $reason = $user['ban_reason'] ?? 'Tài khoản của bạn đã bị khóa bởi quản trị viên.';
            header("Location: ../login.php?error=" . urlencode($reason));
            exit;
        }
        
        // Kiểm tra overdue và lock
        $lock_status = check_overdue_lock_status($conn, $user['id']);
        if ($lock_status['status'] == 'locked') {
             // Store data for locked screen
             $_SESSION['locked_user_id'] = $user['id'];
             $_SESSION['locked_reason'] = $lock_status['reason'];
             $_SESSION['locked_amount'] = $lock_status['shortfall'];
             $_SESSION['locked_penalty'] = $lock_status['penalty'];
             $_SESSION['locked_balance'] = $lock_status['balance'];
             
             header("Location: ../locked.php");
             exit;
        } elseif ($lock_status['status'] == 'warning') {
             $_SESSION['login_warning'] = $lock_status['message'];
        }
        
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['full_name'];
        $_SESSION['customer_email'] = $user['email'];
        header("Location: ../index.php");
        exit;
    } else {
        header("Location: ../login.php?error=Email hoặc mật khẩu không đúng");
        exit;
    }
} else {
    header("Location: ../login.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}
