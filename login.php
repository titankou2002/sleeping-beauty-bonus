<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === ACCESS_PASSWORD) {
        $_SESSION['war_room_auth'] = true;
        $redirect = $_GET['redirect'] ?? 'daily.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = '密碼錯誤，請重新輸入';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>登入戰情室</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23c29d66'/%3E%3Ctext x='32' y='46' font-family='Arial,sans-serif' font-size='40' font-weight='900' fill='%230a0a0a' text-anchor='middle'%3EG%3C/text%3E%3C/svg%3E">
  <style>
    :root {
      --bg: #0a0a0a;
      --paper: #111;
      --border: rgba(194, 157, 102, 0.28);
      --text: #f6f1e6;
      --accent: #c29d66;
      --gold: #f0cb84;
      --red: #ef4444;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Noto Sans TC", "PingFang TC", sans-serif;
      background: radial-gradient(circle at center, rgba(194, 157, 102, 0.08), transparent 60%) var(--bg);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .login-card {
      background: var(--paper);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 40px 30px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.5);
      text-align: center;
    }
    .logo {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      background: var(--accent);
      color: var(--bg);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: 900;
      margin-bottom: 20px;
    }
    h1 {
      margin: 0 0 10px 0;
      font-size: 24px;
      color: var(--gold);
      font-weight: 800;
    }
    p.desc {
      font-size: 13px;
      color: #a9a39a;
      margin: 0 0 30px 0;
    }
    .form-group {
      margin-bottom: 24px;
      text-align: left;
    }
    label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 8px;
    }
    input[type="password"] {
      width: 100%;
      height: 48px;
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--text);
      padding: 0 16px;
      font-size: 16px;
      letter-spacing: 4px;
      text-align: center;
      transition: all 0.2s;
    }
    input[type="password"]:focus {
      border-color: var(--gold);
      background: rgba(194, 157, 102, 0.08);
      outline: none;
      box-shadow: 0 0 10px rgba(194, 157, 102, 0.2);
    }
    .btn-submit {
      width: 100%;
      height: 48px;
      background: linear-gradient(180deg, rgba(194, 157, 102, 0.6), rgba(194, 157, 102, 0.3));
      border: 1px solid var(--gold);
      border-radius: 6px;
      color: var(--gold);
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-submit:hover {
      background: var(--accent);
      color: var(--bg);
      transform: translateY(-1px);
    }
    .error-msg {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: var(--red);
      padding: 10px;
      border-radius: 6px;
      font-size: 13px;
      margin-bottom: 20px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="logo">AW</div>
    <h1>全集團戰情室</h1>
    <p class="desc">請輸入密碼以存取戰情系統</p>
    
    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="form-group">
        <label for="password">戰情密碼</label>
        <input type="password" id="password" name="password" required autofocus placeholder="••••">
      </div>
      <button type="submit" class="btn-submit">驗證登入</button>
    </form>
  </div>
</body>
</html>

