<?php
declare(strict_types=1);

// エラー表示を強制ON
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/auth.php';

    $bootstrapPath = realpath(__DIR__ . '/../api/bootstrap.php');
    if (!$bootstrapPath || !file_exists($bootstrapPath)) {
        throw new RuntimeException("bootstrap.php が見つかりません: " . __DIR__ . '/../api/bootstrap.php');
    }
    require_once $bootstrapPath;

    $pdo = getDb();
    $message = '';
    $error = '';

    // 企業新規登録
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_company') {
        $code  = trim($_POST['company_code'] ?? '');
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (!$code || !$name || !$email || !$pass) {
            $error = 'すべての項目を入力してください。';
        } elseif (!preg_match('/^[a-z0-9\-_]+$/i', $code)) {
            $error = '企業コードは半角英数字、ハイフン、アンダースコアのみ使用可能です。';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO companies (id, company_code, name, email, password_hash, is_active)
                    VALUES (:id, :code, :name, :email, :pass, 1)
                ");
                $stmt->execute([
                    'id'    => generateUuidV4(),
                    'code'  => strtolower($code),
                    'name'  => $name,
                    'email' => $email,
                    'pass'  => password_hash($pass, PASSWORD_DEFAULT),
                ]);
                $message = "企業アカウント「{$name}」を正常に発行・有効化しました。";
            } catch (PDOException $e) {
                $error = '登録エラー（企業コードまたはメールアドレスが既に使用されています）: ' . $e->getMessage();
            }
        }
    }

    // 企業状態切り替え（有効/停止）
    if (isset($_GET['toggle_id'])) {
        $toggleId = $_GET['toggle_id'];
        $stmt = $pdo->prepare("UPDATE companies SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id");
        $stmt->execute(['id' => $toggleId]);
        header('Location: index.php');
        exit;
    }

    // 企業一覧と統計取得（SQLの strict GROUP BY エラーを防止する安全なクエリ）
    $stmt = $pdo->query("
        SELECT c.id, c.company_code, c.name, c.email, c.is_active, c.created_at,
               COALESCE(stats.total_candidates, 0) AS total_candidates,
               COALESCE(stats.completed_interviews, 0) AS completed_interviews
        FROM companies c
        LEFT JOIN (
            SELECT company_id,
                   COUNT(id) AS total_candidates,
                   SUM(CASE WHEN interview_status = 'completed' THEN 1 ELSE 0 END) AS completed_interviews
            FROM candidates
            GROUP BY company_id
        ) stats ON c.id = stats.company_id
        ORDER BY c.created_at DESC
    ");
    $companies = $stmt->fetchAll();

    // 全体メトリクスの集計
    $totalCompaniesCount = count($companies);
    $totalCandidatesSum = array_sum(array_column($companies, 'total_candidates'));
    $totalCompletedSum = array_sum(array_column($companies, 'completed_interviews'));

    $baseUrl = rtrim($_ENV['APP_BASE_URL'] ?? 'https://ai-mensetu.ipogy.org', '/');

} catch (\Throwable $e) {
    echo "<div style='background:#fee2e2;color:#991b1b;padding:1.5rem;margin:1rem;border-radius:8px;font-family:monospace;'>";
    echo "<h3>[Dashboard Error] 500エラーの詳細:</h3>";
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
  <title>利用企業統括管理ポータル | AI面接選考システム</title>
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
      --success-hover: #047857;
      --success-light: #ecfdf5;
      --success-border: #a7f3d0;

      --danger: #dc2626;
      --danger-hover: #b91c1c;
      --danger-light: #fef2f2;
      --danger-border: #fecaca;

      --warning: #d97706;
      --warning-light: #fffbeb;
      --warning-border: #fde68a;

      --radius: 8px;
      --radius-sm: 6px;
      --shadow-sm: 0 1px 2px 0 rgba(15, 23, 42, 0.05);
      --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
      --shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.1);
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
      line-height: 1.5;
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
      max-width: 1240px;
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

    .role-tag {
      font-size: 0.72rem;
      font-weight: 700;
      background: var(--surface-subtle);
      color: var(--text-secondary);
      border: 1px solid var(--border);
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      letter-spacing: 0.05em;
    }

    .nav-logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--danger);
      text-decoration: none;
      padding: 0.45rem 0.75rem;
      border-radius: var(--radius-sm);
      border: 1px solid transparent;
      transition: background-color 0.15s ease;
    }

    .nav-logout-btn:hover {
      background: var(--danger-light);
    }

    /* メインコンテナ */
    main {
      flex: 1;
      max-width: 1240px;
      width: 100%;
      margin: 0 auto;
      padding: 2.25rem 1.25rem 4rem;
    }

    .page-title-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .page-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.01em;
    }

    .page-desc {
      font-size: 0.88rem;
      color: var(--text-secondary);
    }

    /* メトリクス KPI カード */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
      margin-bottom: 1.75rem;
    }

    .kpi-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .kpi-label {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .kpi-value-wrap {
      display: flex;
      align-items: baseline;
      gap: 0.35rem;
    }

    .kpi-value {
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--text-primary);
      line-height: 1.2;
      font-feature-settings: "tnum";
    }

    .kpi-unit {
      font-size: 0.85rem;
      color: var(--text-tertiary);
      font-weight: 600;
    }

    /* アラート */
    .alert {
      padding: 0.85rem 1.15rem;
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

    /* カード共通 */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.75rem;
      margin-bottom: 1.75rem;
      box-shadow: var(--shadow-sm);
    }

    .card-header-line {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
    }

    .card-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    /* フォーム要素 */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.15rem;
      margin-bottom: 1.25rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    label {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .label-hint {
      font-size: 0.72rem;
      color: var(--text-tertiary);
      font-weight: normal;
    }

    input[type="text"],
    input[type="email"] {
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
    input[type="email"]:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* ボタン共通 */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0.45rem 0.75rem;
      min-height: 36px;
      border-radius: var(--radius-sm);
      border: 1px solid transparent;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
      font-family: inherit;
    }

    .btn:active {
      transform: translateY(1px);
    }

    .btn-primary {
      background: var(--brand);
      color: #fff;
    }
    .btn-primary:hover {
      background: var(--brand-hover);
    }

    .btn-secondary {
      background: var(--surface);
      color: var(--text-primary);
      border-color: var(--border-strong);
    }
    .btn-secondary:hover {
      background: var(--surface-subtle);
      border-color: var(--text-secondary);
    }

    .btn-danger-outline {
      background: var(--surface);
      color: var(--danger);
      border-color: var(--danger-border);
    }
    .btn-danger-outline:hover {
      background: var(--danger-light);
      border-color: var(--danger);
    }

    .btn-resume {
      background: var(--brand-light);
      color: var(--brand-accent);
      border-color: #bfdbfe;
    }
    .btn-resume:hover {
      background: #dbeafe;
      border-color: var(--brand-accent);
    }

    /* データテーブル（PC） */
    .table-container {
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      background: var(--surface);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    th {
      background: var(--surface-subtle);
      padding: 0.85rem 1rem;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-secondary);
      border-bottom: 1px solid var(--border);
      letter-spacing: 0.03em;
    }

    td {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.85rem;
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover {
      background: #fafcff;
    }

    .company-name {
      font-weight: 700;
      color: var(--text-primary);
      font-size: 0.92rem;
    }

    .company-code {
      font-size: 0.75rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: var(--brand-accent);
      background: var(--brand-light);
      padding: 0.1rem 0.35rem;
      border-radius: 3px;
      display: inline-block;
      margin-top: 0.2rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      padding: 0.25rem 0.55rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 700;
      line-height: 1;
      white-space: nowrap;
    }

    .badge-active {
      background: var(--success-light);
      color: var(--success);
      border: 1px solid var(--success-border);
    }

    .badge-inactive {
      background: var(--danger-light);
      color: var(--danger);
      border: 1px solid var(--danger-border);
    }

    .num-emphasis {
      font-weight: 700;
      color: var(--text-primary);
      font-feature-settings: "tnum";
    }

    /* 空ステート */
    .empty-state {
      padding: 3.5rem 1.5rem;
      text-align: center;
      color: var(--text-secondary);
    }
    .empty-state svg {
      color: var(--text-tertiary);
      margin-bottom: 0.75rem;
    }

    /* モバイル専用カードビュー */
    .mobile-cards-list {
      display: none;
      flex-direction: column;
      gap: 1rem;
    }

    .tenant-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.15rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }

    .card-row-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 0.5rem;
    }

    .card-detail-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
      padding: 0.75rem 0;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      font-size: 0.82rem;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .detail-label {
      font-size: 0.72rem;
      color: var(--text-tertiary);
      font-weight: 600;
    }

    .card-actions-row {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .card-actions-row .btn {
      flex: 1;
      min-height: 42px; /* スマホタップ領域 */
    }

    /* トースト通知 */
    .toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: var(--text-primary);
      color: #fff;
      padding: 0.75rem 1.25rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 600;
      box-shadow: var(--shadow-md);
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 0.2s ease, transform 0.2s ease;
      z-index: 2000;
      pointer-events: none;
    }
    .toast.show {
      opacity: 1;
      transform: translateY(0);
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

    /* レスポンシブ最適化 (900px以下) */
    @media (max-width: 900px) {
      main {
        padding: 1.5rem 1rem 3rem;
      }
      .card {
        padding: 1.25rem;
      }
      .form-grid {
        grid-template-columns: 1fr;
      }
      .table-container {
        display: none;
      }
      .mobile-cards-list {
        display: flex;
      }
      .toast {
        left: 1rem;
        right: 1rem;
        bottom: 1.5rem;
        text-align: center;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <div class="brand-wrap">
      <div class="brand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
          <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
          <line x1="12" y1="19" x2="12" y2="22"/>
        </svg>
      </div>
      <span class="brand-title">AI面接選考システム</span>
      <span class="role-tag">SUPER ADMIN</span>
    </div>
    <a href="login.php?action=logout" class="nav-logout-btn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" x2="9" y1="12" y2="12"></line>
      </svg>
      <span>ログアウト</span>
    </a>
  </div>
</header>

<main>
  <div class="page-title-row">
    <div>
      <h1 class="page-title">システム統括管理ポータル</h1>
      <p class="page-desc">テナント企業アカウントの発行・停止、およびシステム全体の選考稼働状況を監視します。</p>
    </div>
  </div>

  <!-- KPI メトリクスサマリー -->
  <section class="kpi-grid">
    <div class="kpi-card">
      <span class="kpi-label">契約・登録企業数</span>
      <div class="kpi-value-wrap">
        <span class="kpi-value"><?= number_format($totalCompaniesCount) ?></span>
        <span class="kpi-unit">社</span>
      </div>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">累計総応募者数</span>
      <div class="kpi-value-wrap">
        <span class="kpi-value"><?= number_format($totalCandidatesSum) ?></span>
        <span class="kpi-unit">名</span>
      </div>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">実施済 AI面接数</span>
      <div class="kpi-value-wrap">
        <span class="kpi-value"><?= number_format($totalCompletedSum) ?></span>
        <span class="kpi-unit">件</span>
      </div>
    </div>
  </section>

  <?php if ($message !== ''): ?>
    <div class="alert alert-success" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- 新規企業アカウント発行カード -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="8.5" cy="7.5" r="4"/>
          <line x1="20" y1="8" x2="20" y2="14"/>
          <line x1="23" y1="11" x2="17" y2="11"/>
        </svg>
        <span>新規企業アカウント発行</span>
      </h2>
    </div>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="action" value="create_company">
      <div class="form-grid">
        <div class="form-group">
          <label for="company_name">企業名・組織名</label>
          <input type="text" id="company_name" name="name" required placeholder="例: トヨタ自動車株式会社">
        </div>
        <div class="form-group">
          <label for="company_code">
            <span>企業コード (URL用)</span>
            <span class="label-hint">英数・ハイフンのみ</span>
          </label>
          <input type="text" id="company_code" name="company_code" required placeholder="例: toyota" pattern="[a-zA-Z0-9\-_]+">
        </div>
        <div class="form-group">
          <label for="company_email">管理者ログインID (Email)</label>
          <input type="email" id="company_email" name="email" required placeholder="hr@example.com">
        </div>
        <div class="form-group">
          <label for="company_pass">初期パスワード</label>
          <input type="text" id="company_pass" name="password" required placeholder="初回仮パスワード">
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="min-height: 42px; padding: 0 1.5rem;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        <span>企業アカウントを発行・有効化</span>
      </button>
    </form>
  </section>

  <!-- 契約企業一覧 -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
          <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
        </svg>
        <span>契約テナント企業一覧 (<?= count($companies) ?>社)</span>
      </h2>
    </div>

    <!-- PC表示: テーブル -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>企業名 / 識別コード</th>
            <th>管理者メールアドレス</th>
            <th>ステータス</th>
            <th>総応募者数</th>
            <th>面接完了数</th>
            <th>専用エントリーURL</th>
            <th>登録日時</th>
            <th style="text-align: right;">アカウント制御</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($companies)): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <p>現在登録されている企業アカウントはありません。</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($companies as $c): ?>
          <?php $entryUrl = "{$baseUrl}/entry.html?company=" . urlencode($c['company_code']); ?>
          <tr>
            <td>
              <div class="company-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <span class="company-code"><?= htmlspecialchars($c['company_code'], ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="status-badge <?= $c['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                <?= $c['is_active'] ? '稼働中' : '停止中' ?>
              </span>
            </td>
            <td><span class="num-emphasis"><?= number_format((int)$c['total_candidates']) ?></span> 名</td>
            <td><span class="num-emphasis"><?= number_format((int)$c['completed_interviews']) ?></span> 件</td>
            <td>
              <button type="button" class="btn btn-secondary" onclick="copyEntryUrl('<?= htmlspecialchars($entryUrl, ENT_QUOTES, 'UTF-8') ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                  <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                </svg>
                <span>URLコピー</span>
              </button>
            </td>
            <td>
              <span style="color: var(--text-tertiary); font-size: 0.8rem;"><?= htmlspecialchars(substr($c['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td style="text-align: right;">
              <a href="?toggle_id=<?= urlencode((string)$c['id']) ?>" 
                 class="btn <?= $c['is_active'] ? 'btn-danger-outline' : 'btn-resume' ?>" 
                 onclick="return confirm('<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> のアカウント状態を【<?= $c['is_active'] ? '利用停止' : '利用再開' ?>】に変更しますか？');">
                <?= $c['is_active'] ? 'アカウント停止' : '利用を再開' ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- スマホ表示: カードスタックリスト -->
    <div class="mobile-cards-list">
      <?php if (empty($companies)): ?>
        <div class="empty-state" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);">
          <p>現在登録されている企業アカウントはありません。</p>
        </div>
      <?php endif; ?>

      <?php foreach ($companies as $c): ?>
      <?php $entryUrl = "{$baseUrl}/entry.html?company=" . urlencode($c['company_code']); ?>
      <div class="tenant-card">
        <div class="card-row-top">
          <div>
            <div class="company-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <span class="company-code"><?= htmlspecialchars($c['company_code'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div>
            <span class="status-badge <?= $c['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $c['is_active'] ? '稼働中' : '停止中' ?>
            </span>
          </div>
        </div>

        <div style="font-size: 0.82rem; color: var(--text-secondary);">
          <span>管理ID: </span><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="card-detail-group">
          <div class="detail-item">
            <span class="detail-label">応募者数</span>
            <span class="num-emphasis"><?= number_format((int)$c['total_candidates']) ?> 名</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">面接完了数</span>
            <span class="num-emphasis"><?= number_format((int)$c['completed_interviews']) ?> 件</span>
          </div>
        </div>

        <div class="card-actions-row">
          <button type="button" class="btn btn-secondary" onclick="copyEntryUrl('<?= htmlspecialchars($entryUrl, ENT_QUOTES, 'UTF-8') ?>')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
              <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
            </svg>
            <span>URLコピー</span>
          </button>
          <a href="?toggle_id=<?= urlencode((string)$c['id']) ?>" 
             class="btn <?= $c['is_active'] ? 'btn-danger-outline' : 'btn-resume' ?>" 
             onclick="return confirm('<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> のアカウント状態を【<?= $c['is_active'] ? '利用停止' : '利用再開' ?>】に変更しますか？');">
            <?= $c['is_active'] ? '停止する' : '再開する' ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<!-- コピー通知トースト -->
<div class="toast" id="toastMessage">企業専用エントリーURLをコピーしました</div>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

<script>
  function showToast(text) {
    const toast = document.getElementById('toastMessage');
    toast.textContent = text;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 2400);
  }

  function copyEntryUrl(url) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(() => {
        showToast('企業専用エントリーURLをコピーしました');
      }).catch(() => {
        fallbackCopy(url);
      });
    } else {
      fallbackCopy(url);
    }
  }

  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      showToast('企業専用エントリーURLをコピーしました');
    } catch (err) {
      prompt('URLをコピーしてください:', text);
    }
    document.body.removeChild(ta);
  }
</script>

</body>
</html>