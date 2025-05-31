<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    die("Not logged in!");
}

// Load configuration
function loadConfig() {
    if (!file_exists('config.json')) {
        return ['password' => password_hash('changeme', PASSWORD_DEFAULT)];
    }
    return json_decode(file_get_contents('config.json'), true);
}

// Save configuration
function saveConfig($config) {
    return file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$config = loadConfig();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request";
    } elseif (empty($_POST['old']) || empty($_POST['new']) || empty($_POST['confirm'])) {
        $error = "All fields must be filled";
    } elseif (!password_verify($_POST['old'], $config['password'])) {
        $error = "Current password is incorrect";
    } elseif ($_POST['new'] !== $_POST['confirm']) {
        $error = "New passwords do not match";
    } elseif (strlen($_POST['new']) < 6) {
        $error = "New password must be at least 6 characters long";
    } else {
        $config['password'] = password_hash($_POST['new'], PASSWORD_DEFAULT);
        if (saveConfig($config)) {
            $success = "Password changed successfully!";
        } else {
            $error = "Error saving new password";
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .password-requirements h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .password-requirements ul {
            color: #6c757d;
            font-size: 13px;
            margin-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 3px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Change Password</h1>
            <p>Change your login password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="password-requirements">
            <h4>📋 Password Requirements:</h4>
            <ul>
                <li>At least 6 characters long</li>
                <li>Use a combination of letters, numbers and special characters</li>
                <li>Avoid simple words or personal information</li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="old">Current Password</label>
                <input type="password" id="old" name="old" required>
            </div>

            <div class="form-group">
                <label for="new">New Password</label>
                <input type="password" id="new" name="new" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm">Confirm New Password</label>
                <input type="password" id="confirm" name="confirm" required minlength="6">
            </div>

            <button type="submit" class="btn">💾 Change Password</button>
        </form>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm').addEventListener('input', function() {
            const newPassword = document.getElementById('new').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
