<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/bootstrap.php';

$companyId = $_SESSION['company_id'];
$pdo = getDb();

$message = '';
$error = '';

// 最新の企業情報を取得
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $companyId]);
$company = $stmt->fetch();

if (!$company) {
    die('企業アカウント情報が見つかりません。');
}

// 保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $curPass  = trim($_POST['current_password'] ?? '');
    $newPass  = trim($_POST['new_password'] ?? '');
    $newPassC = trim($_POST['new_password_confirm'] ?? '');

    if (!$name || !$email) {
        $error = '企業名とメールアドレスは必須です。';
    } else {
        // 現在のパスワード検証
        if (!password_verify($curPass, $company['password_hash'])) {
            $error = '現在のパスワードが正しくありません。';
        } else {
            // パスワード変更がある場合のチェック
            $updatePassword = false;
            if ($newPass !== '') {
                if (strlen($newPass) < 6) {
                    $error = '新しいパスワードは6文字以上で入力してください。';
                } elseif ($newPass !== $newPassC) {
                    $error = '新しいパスワード（確認用）が一致しません。';
                } else {
                    $updatePassword = true;
                }
            }

            if (!$error) {
                try {
                    // 他社とメールアドレスが被っていないかチェック
                    $stmtCheck = $pdo->prepare("SELECT id FROM companies WHERE email = :email AND id != :id LIMIT 1");
                    $stmtCheck->execute(['email' => $email, 'id' => $companyId]);
                    if ($stmtCheck->fetch()) {
                        $error = 'そのメールアドレスは既に他のアカウントで使用されています。';
                    } else {
                        if ($updatePassword) {
                            $stmtUp = $pdo->prepare("
                                UPDATE companies 
                                SET name = :name, email = :email, password_hash = :pass 
                                WHERE id = :id
                            ");
                            $stmtUp->execute([
                                'name'  => $name,
                                'email' => $email,
                                'pass'  => password_hash($newPass, PASSWORD_DEFAULT),
                                'id'    => $companyId
                            ]);
                        } else {
                            $stmtUp = $pdo->prepare("
                                UPDATE companies 
                                SET name = :name, email = :email 
                                WHERE id = :id
                            ");
                            $stmtUp->execute([
                                'name'  => $name,
                                'email' => $email,
                                'id'    => $companyId
                            ]);
                        }

                        // セッション情報も同期更新
                        $_SESSION['company_name'] = $name;

                        // 画面表示用データ更新
                        $company['name'] = $name;
                        $company['email'] = $email;

                        $message = '企業アカウント設定を正常に更新・保存しました。';
                    }
                } catch (PDOException $e) {
                    $error = '更新エラー: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>企業アカウント設定 | <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></title>
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
      --brand-light: #eff6ff;

      --success: #059669;
      --success-light: #ecfdf5;
      --success-border: #a7f3d0;

      --danger: #dc2626;
      --danger-light: #fef2f2;
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
      position: sticky;
      top: 0;
      z-index: 30;
    }

    .header-inner {
      max-width: 900px;
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
      max-width: 900px;
      width: 100%;
      margin: 0 auto;
      padding: 2.25rem 1.25rem 4rem;
    }

    .page-header {
      margin-bottom: 1.75rem;
    }

    .page-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.01em;
      margin-bottom: 0.35rem;
    }

    .page-desc {
      font-size: 0.88rem;
      color: var(--text-secondary);
    }

    /* アラート */
    .alert {
      padding: 0.85rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-success {
      background: var(--success-light);
      border: 1px solid var(--success-border);
      color: var(--success);
    }

    .alert-error {
      background: var(--danger-light);
      border: 1px solid var(--danger-border);
      color: var(--danger);
    }

    /* フォームカード */
    .form-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2rem;
      box-shadow: var(--shadow-sm);
    }

    .section-title-wrap {
      margin-bottom: 1.25rem;
    }

    .section-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    .section-desc {
      font-size: 0.82rem;
      color: var(--text-secondary);
      margin-top: 0.2rem;
    }

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

    .label-badge {
      font-size: 0.72rem;
      padding: 0.15rem 0.45rem;
      border-radius: 4px;
      font-weight: 700;
      margin-left: 0.35rem;
      vertical-align: middle;
    }

    .badge-required {
      background: var(--danger-light);
      color: var(--danger);
      border: 1px solid var(--danger-border);
    }

    .badge-readonly {
      background: var(--surface-subtle);
      color: var(--text-tertiary);
      border: 1px solid var(--border);
    }

    input[type="text"],
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

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    input:disabled {
      background: var(--surface-subtle);
      border-color: var(--border);
      color: var(--text-tertiary);
      cursor: not-allowed;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .field-hint {
      display: block;
      font-size: 0.75rem;
      color: var(--text-tertiary);
      margin-top: 0.35rem;
    }

    /* 区切り線とセクション */
    .form-divider {
      border: 0;
      height: 1px;
      background: var(--border);
      margin: 2rem 0 1.75rem;
    }

    /* 本人確認ブロック（安全性を意識したアクセント） */
    .auth-verify-block {
      background: #fafcff;
      border: 1px solid #bfdbfe;
      border-radius: var(--radius-sm);
      padding: 1.25rem;
      margin-top: 1.5rem;
    }

    .auth-verify-block label {
      color: var(--brand-hover);
    }

    /* ボタン */
    .btn-submit {
      width: 100%;
      height: 46px;
      background: var(--brand);
      color: #fff;
      border: 1px solid transparent;
      border-radius: var(--radius-sm);
      font-size: 0.92rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 1.5rem;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 0.45rem;
      transition: background-color 0.15s ease;
      font-family: inherit;
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
      margin-top: auto;
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
        padding: 1.5rem 1rem 3rem;
      }
      .form-card {
        padding: 1.5rem 1.25rem;
      }
      input[type="text"],
      input[type="email"],
      input[type="password"] {
        height: 44px;
      }
      .btn-submit {
        height: 48px;
        font-size: 0.95rem;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <a href="/admin/index.php" class="brand-wrap">
      <div class="brand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
          <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
          <line x1="12" y1="19" x2="12" y2="22"/>
        </svg>
      </div>
      <span class="brand-title">AI面接選考システム</span>
    </a>
    <a href="/admin/index.php" class="nav-back-link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
      </svg>
      <span>ダッシュボードへ戻る</span>
    </a>
  </div>
</header>

<main>
  <div class="page-header">
    <h1 class="page-title">企業アカウント設定</h1>
    <p class="page-desc">企業基本情報やログイン通知用メールアドレス、パスワードの変更を管理します。</p>
  </div>

  <?php if ($message !== ''): ?>
    <div class="alert alert-success" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" autocomplete="off">
      <div class="section-title-wrap">
        <h2 class="section-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
          </svg>
          <span>基本情報</span>
        </h2>
      </div>

      <div class="form-group">
        <label>
          <span>テナント企業コード</span>
          <span class="label-badge badge-readonly">変更不可</span>
        </label>
        <input type="text" value="<?= htmlspecialchars((string)$company['company_code'], ENT_QUOTES, 'UTF-8') ?>" disabled>
        <small class="field-hint">専用エントリーURLの識別パラメータ（?company=...）として利用されます。</small>
      </div>

      <div class="form-group">
        <label for="company_name">
          <span>企業名・組織名</span>
          <span class="label-badge badge-required">必須</span>
        </label>
        <input type="text" id="company_name" name="name" required value="<?= htmlspecialchars((string)$company['name'], ENT_QUOTES, 'UTF-8') ?>">
        <small class="field-hint">候補者向けのエントリー画面および自動配信メールの署名に表示されます。</small>
      </div>

      <div class="form-group">
        <label for="company_email">
          <span>管理ログイン用メールアドレス</span>
          <span class="label-badge badge-required">必須</span>
        </label>
        <input type="email" id="company_email" name="email" required value="<?= htmlspecialchars((string)$company['email'], ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <hr class="form-divider">

      <div class="section-title-wrap">
        <h2 class="section-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          <span>パスワードの更新</span>
        </h2>
        <p class="section-desc">パスワードを変更しない場合は空欄のままにしてください。</p>
      </div>

      <div class="form-group">
        <label for="new_password">新しいパスワード</label>
        <input type="password" id="new_password" name="new_password" placeholder="6文字以上で入力" autocomplete="new-password">
      </div>

      <div class="form-group">
        <label for="new_password_confirm">新しいパスワード（確認用）</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="もう一度入力してください" autocomplete="new-password">
      </div>

      <div class="auth-verify-block">
        <div class="form-group" style="margin-bottom: 0;">
          <label for="current_password">
            <span>現在のパスワード（本人認証）</span>
            <span class="label-badge badge-required">必須</span>
          </label>
          <input type="password" id="current_password" name="current_password" required placeholder="現在のパスワードを入力して認証" autocomplete="current-password">
          <small class="field-hint" style="color: var(--brand-hover);">セキュリティ保護のため、設定変更の確定には現在のパスワードの入力が必要です。</small>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>変更内容を保存する</span>
      </button>
    </form>
  </div>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>