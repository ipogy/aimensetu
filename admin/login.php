<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログアウト処理
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['company_logged_in'], $_SESSION['company_id'], $_SESSION['company_name'], $_SESSION['company_code']);
    header('Location: /admin/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $company = $stmt->fetch();

    if ($company && password_verify($pass, $company['password_hash'])) {
        if (!$company['is_active']) {
            $error = 'この企業アカウントは現在利用停止されています。システム管理者へお問い合わせください。';
        } else {
            $_SESSION['company_logged_in'] = true;
            $_SESSION['company_id']        = $company['id'];
            $_SESSION['company_name']      = $company['name'];
            $_SESSION['company_code']      = $company['company_code'];
            header('Location: /admin/index.php');
            exit;
        }
    } else {
        $error = 'メールアドレスまたはパスワードが正しくありません。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>採用担当者ログイン | AI面接選考システム</title>
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
      align-items: flex-start;
      gap: 0.5rem;
    }

    .alert-error svg {
      flex-shrink: 0;
      margin-top: 0.2rem;
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

    input[type="email"],
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

    input[type="email"]::placeholder,
    input[type="password"]::placeholder {
      color: var(--text-tertiary);
    }

    input[type="email"]:focus,
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

    /* スマホ最適化 */
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
      input[type="email"],
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
          <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
          <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
        </svg>
        <span>RECRUITER CONSOLE</span>
      </div>
      <h1>採用担当者コンソール</h1>
      <p class="subtitle">選考管理・応募者データ閲覧のための認証画面です</p>
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

    <form method="POST" autocomplete="on">
      <div class="form-group">
        <label for="email">企業用登録メールアドレス</label>
        <input type="email" id="email" name="email" required autofocus placeholder="hr@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="form-group">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
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