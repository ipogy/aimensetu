<?php
declare(strict_types=1);

// エラー表示を強制ON
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    // 確実にパスを解決
    $bootstrapPath = realpath(__DIR__ . '/../api/bootstrap.php');
    if (!$bootstrapPath || !file_exists($bootstrapPath)) {
        throw new RuntimeException("bootstrap.php が見つかりません: " . __DIR__ . '/../api/bootstrap.php');
    }
    require_once $bootstrapPath;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        unset($_SESSION['super_admin_logged_in'], $_SESSION['super_admin_user']);
        header('Location: login.php');
        exit;
    }

    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        // .env から取得、未設定時のデフォルト値も用意
        $expectedUser = $_ENV['SUPER_ADMIN_USER'] ?? 'superadmin';
        $expectedPass = $_ENV['SUPER_ADMIN_PASS'] ?? 'supersecret2026';

        if ($user !== '' && $user === $expectedUser && $pass === $expectedPass) {
            $_SESSION['super_admin_logged_in'] = true;
            $_SESSION['super_admin_user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $error = 'IDまたはパスワードが正しくありません。';
        }
    }
} catch (\Throwable $e) {
    echo "<div style='background:#fee2e2;color:#991b1b;padding:1.5rem;margin:1rem;border-radius:8px;font-family:monospace;'>";
    echo "<h3>[Login Error] 500エラーの詳細:</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>システム統括管理ログイン | AI面接選考システム</title>
  <style>
    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --slate-900: #0f172a;
      --slate-800: #1e293b;
      --slate-600: #475569;
      --slate-500: #64748b;
      --slate-100: #f1f5f9;
      --border: #e2e8f0;
      --danger: #dc2626;
      --danger-bg: #fef2f2;
      --danger-border: #fecaca;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
      color: var(--slate-800);
      background: #f8fafc;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      line-height: 1.6;
    }

    header {
      background: #fff;
      border-bottom: 1px solid var(--border);
    }
    .header-inner {
      max-width: 1140px;
      margin: 0 auto;
      padding: 0.9rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      font-weight: 800;
      font-size: 1.15rem;
      color: var(--slate-900);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .top-link {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--slate-600);
      text-decoration: none;
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: #fff;
      transition: all 0.2s ease;
    }
    .top-link:hover {
      background: var(--slate-100);
      color: var(--slate-900);
    }

    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2.5rem 1.5rem;
    }

    .card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 2.5rem 2rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
      width: 100%;
      max-width: 420px;
    }

    .card-header {
      text-align: center;
      margin-bottom: 1.8rem;
    }
    .badge {
      display: inline-block;
      background: var(--slate-900);
      color: #fff;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      margin-bottom: 0.75rem;
      letter-spacing: 0.05em;
    }
    h1 {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--slate-900);
      letter-spacing: -0.01em;
    }
    .subtitle {
      font-size: 0.85rem;
      color: var(--slate-500);
      margin-top: 0.35rem;
    }

    .error {
      background: var(--danger-bg);
      border: 1px solid var(--danger-border);
      color: var(--danger);
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }
    label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--slate-800);
      margin-bottom: 0.4rem;
    }
    input {
      width: 100%;
      padding: 0.75rem 0.9rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.95rem;
      background: #fff;
      color: var(--slate-900);
      transition: all 0.2s ease;
    }
    input::placeholder {
      color: #94a3b8;
    }
    input:focus {
      outline: none;
      border-color: var(--slate-900);
      box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1);
    }

    .btn-submit {
      width: 100%;
      padding: 0.8rem;
      background: var(--slate-900);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 0.6rem;
      transition: background 0.2s ease;
      display: inline-flex;
      justify-content: center;
      align-items: center;
    }
    .btn-submit:hover {
      background: var(--slate-800);
    }

    footer {
      border-top: 1px solid var(--border);
      background: #fff;
      padding: 1.25rem;
      text-align: center;
      color: var(--slate-500);
      font-size: 0.8rem;
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <a href="/" class="logo">
      <span>🎙️ AI面接選考システム</span>
    </a>
    <a href="/" class="top-link">← トップへ戻る</a>
  </div>
</header>

<main>
  <div class="card">
    <div class="card-header">
      <div class="badge">SUPER ADMIN</div>
      <h1>統括管理ポータル</h1>
      <p class="subtitle">プラットフォーム全体管理者専用の認証画面です</p>
    </div>

    <?php if ($error): ?>
      <div class="error">
        <span>⚠️</span> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">管理者ID</label>
        <input type="text" id="username" name="username" required autofocus placeholder="superadmin">
      </div>
      <div class="form-group">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">ログイン</button>
    </form>
  </div>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>