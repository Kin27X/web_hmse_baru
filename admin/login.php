<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - HMSE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        *{ box-sizing:border-box; margin:0; padding:0; }
        body{
            font-family:'Montserrat', sans-serif;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(160deg,#05080f,#0b0a2e);
            padding:20px;
        }
        .login-box{
            width:100%;
            max-width:360px;
            background:#ffffff;
            border-radius:22px;
            padding:36px 32px;
            box-shadow:0 30px 60px rgba(0,0,0,0.35);
        }
        .login-box h1{
            font-size:22px;
            font-weight:700;
            color:#171533;
            margin-bottom:4px;
        }
        .login-box p.subtitle{
            font-size:13px;
            color:rgba(23,21,51,0.55);
            margin-bottom:26px;
        }
        label{
            display:block;
            font-size:12.5px;
            font-weight:600;
            color:rgba(23,21,51,0.6);
            margin-bottom:6px;
        }
        input{
            width:100%;
            padding:11px 14px;
            border-radius:12px;
            border:1px solid rgba(23,21,51,0.15);
            font-family:inherit;
            font-size:14px;
            margin-bottom:18px;
            outline:none;
        }
        input:focus{
            border-color:#2d1fd6;
        }
        button{
            width:100%;
            padding:12px;
            border:none;
            border-radius:50px;
            background:#2d1fd6;
            color:#fff;
            font-family:inherit;
            font-weight:700;
            font-size:14.5px;
            cursor:pointer;
        }
        .error-msg{
            background:#fdeaea;
            color:#c0392b;
            font-size:13px;
            padding:10px 14px;
            border-radius:10px;
            margin-bottom:18px;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h1>Admin HMSE</h1>
        <p class="subtitle">Masuk untuk mengelola News, Gallery, dan Struktur Organisasi.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>

            <button type="submit">Masuk</button>
        </form>
    </div>

</body>
</html>
