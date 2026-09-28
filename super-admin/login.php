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
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . " (Line: " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>システム統括管理ログイン | AI面接選考システム</title>
  <style>
    :root {
      --bg: #f8fafc;
      --surface: #ffffff;
      --surface-subtle: #f1f5f9;
      --border: #e2e8f0;
      --border-strong: #cbd5e1;
      
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --text-tertiary: #94a3b8;
      
      --brand: #1e3a8a;          /* ディープネイビー */
      --brand-hover: #172554;
      --brand-accent: #2563eb;
      
      --danger: #dc2626;
      --danger-bg: #fef2f2;
      --danger-border: #fecaca;

      --radius: 8px;
      --radius-sm: 6px;
      --shadow-sm: 0 1px 2px 0 rgba(15, 23, 42, 0.05);
      --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Hiragino Sans", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
      font-feature-settings: "palt";
      color: var(--text-primary);
      background: var(--bg);
      min-height: 100dvh;
      display: flex;
      flex-direction: column;
      -webkit-font-smoothing: antialiased;
      line-height: 1.6;
    }

    /* ヘッダー */
    header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
    }

    .header-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0.85rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
    }

    .brand-wrap {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      text-decoration: none;
      color: var(--text-primary);
    }

    .brand-icon {
      width: 30px;
      height: 30px;
      background: var(--brand);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: var(--radius-sm);
      flex-shrink: 0;
    }

    .brand-title {
      font-weight: 700;
      font-size: 0.98rem;
      letter-spacing: -0.01em;
      white-space: nowrap;
    }

    .nav-back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-secondary);
      text-decoration: none;
      padding: 0.45rem 0.75rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: var(--surface);
      transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .nav-back-link:hover {
      background: var(--surface-subtle);
      color: var(--text-primary);
      border-color: var(--border-strong);
    }

    /* メインコンテナ */
    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2.5rem 1.25rem;
    }

    .login-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2.25rem 2rem;
      box-shadow: var(--shadow-sm);
      width: 100%;
      max-width: 400px;
      transition: box-shadow 0.15s ease;
    }

    .card-header {
      margin-bottom: 1.75rem;
    }

    .role-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background: var(--surface-subtle);
      color: var(--text-secondary);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.25rem 0.6rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
    }

    .role-badge svg {
      color: var(--text-secondary);
    }

    h1 {
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.01em;
      line-height: 1.35;
    }

    .subtitle {
      font-size: 0.85rem;
      color: var(--text-secondary);
      margin-top: 0.35rem;
      line-height: 1.5;
    }

    /* エラーアラート */
    .alert-error {
      background: var(--danger-bg);
      border: 1px solid var(--danger-border);
      color: var(--danger);
      padding: 0.75rem 0.9rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-error svg {
      flex-shrink: 0;
    }

    /* フォーム要素 */
    .form-group {
      margin-bottom: 1.25rem;
    }

    label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.4rem;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      height: 42px;
      padding: 0 0.85rem;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      background: var(--surface);
      color: var(--text-primary);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
      font-family: inherit;
    }

    input[type="text"]::placeholder,
    input[type="password"]::placeholder {
      color: var(--text-tertiary);
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* ログインボタン */
    .btn-submit {
      width: 100%;
      height: 44px;
      background: var(--brand);
      color: #fff;
      border: 1px solid transparent;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 0.5rem;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 0.4rem;
      transition: background-color 0.15s ease;
      user-select: none;
    }

    .btn-submit:hover {
      background: var(--brand-hover);
    }

    .btn-submit:active {
      transform: translateY(1px);
    }

    /* フッター */
    footer {
      border-top: 1px solid var(--border);
      background: var(--surface);
      padding: 1.5rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom));
      text-align: center;
      color: var(--text-tertiary);
      font-size: 0.8rem;
    }

    /* スマホ・小型端末最適化 */
    @media (max-width: 640px) {
      .header-inner {
        padding: 0.75rem 1rem;
      }
      .brand-title {
        font-size: 0.9rem;
      }
      .nav-back-link {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
      }
      main {
        padding: 1.5rem 1rem 2.5rem;
        align-items: flex-start;
      }
      .login-card {
        padding: 1.75rem 1.25rem;
        box-shadow: none;
        border-color: var(--border);
      }
      input[type="text"],
      input[type="password"],
      .btn-submit {
        height: 46px; /* 親指タップ領域の確保 */
      }
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <a href="/" class="brand-wrap">
      <div class="brand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
          <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
          <line x1="12" y1="19" x2="12" y2="22"/>
        </svg>
      </div>
      <span class="brand-title">AI面接選考システム</span>
    </a>
    <a href="/" class="nav-back-link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
      </svg>
      <span>トップへ戻る</span>
    </a>
  </div>
</header>

<main>
  <div class="login-card">
    <div class="card-header">
      <div class="role-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <span>SUPER ADMIN</span>
      </div>
      <h1>システム統括管理ポータル</h1>
      <p class="subtitle">プラットフォーム全体管理者専用の認証画面です</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="username">管理者アカウントID</label>
        <input type="text" id="username" name="username" required autofocus placeholder="アカウントIDを入力">
      </div>
      <div class="form-group">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">
        <span>ログイン</span>
      </button>
    </form>
  </div>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>