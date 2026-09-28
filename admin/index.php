<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/bootstrap.php';

$companyId   = $_SESSION['company_id'];
$companyName = $_SESSION['company_name'];
$companyCode = $_SESSION['company_code'];

$pdo = getDb();

$stmt = $pdo->prepare("
    SELECT c.*, s.id AS session_id, s.ended_at 
    FROM candidates c 
    LEFT JOIN (
        SELECT s1.* FROM interview_sessions s1
        INNER JOIN (
            SELECT candidate_id, MAX(started_at) as max_started 
            FROM interview_sessions GROUP BY candidate_id
        ) s2 ON s1.candidate_id = s2.candidate_id AND s1.started_at = s2.max_started
    ) s ON c.id = s.candidate_id 
    WHERE c.company_id = :cid
    ORDER BY c.created_at DESC
");
$stmt->execute(['cid' => $companyId]);
$candidates = $stmt->fetchAll();

$baseUrl = rtrim($_ENV['APP_BASE_URL'] ?? 'https://ai-mensetu.ipogy.org', '/');
$selfEntryUrl = "{$baseUrl}/entry.html?company=" . urlencode($companyCode);

function getStatusBadge(string $status): string {
    $map = [
        'applied'     => ['class' => 'badge-applied',     'label' => '書類選考中'],
        'ready'       => ['class' => 'badge-ready',       'label' => '面接案内済'],
        'in_progress' => ['class' => 'badge-in-progress', 'label' => '面接実施中'],
        'completed'   => ['class' => 'badge-completed',   'label' => '面接完了'],
        'es_failed'   => ['class' => 'badge-failed',      'label' => '書類見送り'],
    ];
    $item = $map[$status] ?? ['class' => 'badge-applied', 'label' => $status];
    return "<span class=\"badge {$item['class']}\">{$item['label']}</span>";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> - 採用管理コンソール | AI面接選考システム</title>
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

      --danger: #dc2626;
      --danger-hover: #b91c1c;
      --danger-light: #fef2f2;

      --warning: #d97706;
      --warning-hover: #b45309;
      --warning-light: #fffbeb;

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
      z-index: 40;
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

    .header-actions.desktop-nav {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .header-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-secondary);
      text-decoration: none;
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius-sm);
      transition: background-color 0.15s ease, color 0.15s ease;
    }

    .header-link:hover {
      background: var(--surface-subtle);
      color: var(--text-primary);
    }

    .header-link.logout {
      color: var(--danger);
      margin-left: 0.5rem;
    }
    .header-link.logout:hover {
      background: var(--danger-light);
    }

    /* スマホメニュー開閉ボタン */
    .mobile-menu-btn {
      display: none;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      width: 38px;
      height: 38px;
      cursor: pointer;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 4px;
    }

    .mobile-menu-btn .bar {
      width: 18px;
      height: 2px;
      background: var(--text-primary);
      border-radius: 1px;
      transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .mobile-menu-btn[aria-expanded="true"] .bar:nth-child(1) {
      transform: translateY(6px) rotate(45deg);
    }
    .mobile-menu-btn[aria-expanded="true"] .bar:nth-child(2) {
      opacity: 0;
    }
    .mobile-menu-btn[aria-expanded="true"] .bar:nth-child(3) {
      transform: translateY(-6px) rotate(-45deg);
    }

    .mobile-drawer {
      display: none;
    }

    /* メインコンテンツ */
    main {
      flex: 1;
      max-width: 1240px;
      width: 100%;
      margin: 0 auto;
      padding: 2rem 1.25rem 3.5rem;
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
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.01em;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .company-tag {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--brand-accent);
      background: var(--brand-light);
      padding: 0.2rem 0.55rem;
      border-radius: 4px;
      border: 1px solid #bfdbfe;
    }

    /* URL・出力バナーカード */
    .banner-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      box-shadow: var(--shadow-sm);
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.25rem;
    }

    .banner-info {
      flex: 1;
      min-width: 280px;
    }

    .banner-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.35rem;
    }

    .banner-url-box {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius-sm);
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.85rem;
      color: var(--brand-hover);
      word-break: break-all;
    }

    .banner-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
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

    .btn-success {
      background: var(--success);
      color: #fff;
    }
    .btn-success:hover {
      background: var(--success-hover);
    }

    .btn-danger {
      background: var(--surface);
      color: var(--danger);
      border-color: var(--danger-border, #fca5a5);
    }
    .btn-danger:hover {
      background: var(--danger-light);
      border-color: var(--danger);
    }

    .btn-recover {
      background: var(--warning-light);
      color: var(--warning);
      border-color: #fde68a;
    }
    .btn-recover:hover {
      background: #fef3c7;
      border-color: var(--warning);
    }

    /* データテーブル（PC） */
    .table-container {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
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

    .candidate-name {
      font-weight: 700;
      color: var(--text-primary);
      font-size: 0.92rem;
    }

    .candidate-email {
      font-size: 0.78rem;
      color: var(--text-secondary);
      margin-top: 0.15rem;
    }

    .candidate-date {
      font-size: 0.8rem;
      color: var(--text-tertiary);
      white-space: nowrap;
    }

    /* ステータスバッジ（落ち着いたビジネス配色） */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      padding: 0.25rem 0.6rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 700;
      line-height: 1;
      white-space: nowrap;
    }

    .badge-applied {
      background: var(--warning-light);
      color: var(--warning);
      border: 1px solid #fde68a;
    }
    .badge-ready {
      background: var(--brand-light);
      color: var(--brand-accent);
      border: 1px solid #bfdbfe;
    }
    .badge-in-progress {
      background: #fff7ed;
      color: #ea580c;
      border: 1px solid #ffedd5;
    }
    .badge-completed {
      background: var(--success-light);
      color: var(--success);
      border: 1px solid #a7f3d0;
    }
    .badge-failed {
      background: var(--danger-light);
      color: var(--danger);
      border: 1px solid #fecaca;
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

    .candidate-card {
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

    /* ES詳細モーダル */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      padding: 1rem;
    }

    .modal-content {
      background: var(--surface);
      width: 100%;
      max-width: 640px;
      border-radius: var(--radius);
      max-height: 85vh;
      display: flex;
      flex-direction: column;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .modal-header {
      padding: 1.15rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      padding: 0.25rem;
      border-radius: 4px;
      display: flex;
      align-items: center;
    }
    .modal-close-btn:hover {
      background: var(--surface-subtle);
      color: var(--text-primary);
    }

    .modal-body {
      padding: 1.5rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .es-section {
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      padding: 1rem 1.15rem;
      border-radius: var(--radius-sm);
    }

    .es-title {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 0.5rem;
    }

    .es-body-text {
      font-size: 0.88rem;
      color: var(--text-primary);
      white-space: pre-wrap;
      line-height: 1.65;
    }

    .modal-footer {
      padding: 0.85rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      background: var(--surface-subtle);
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
      .header-actions.desktop-nav {
        display: none;
      }
      .mobile-menu-btn {
        display: flex;
      }
      .mobile-drawer {
        display: block;
        max-height: 0;
        overflow: hidden;
        background: var(--surface);
        border-bottom: 1px solid transparent;
        transition: max-height 0.25s ease, border-color 0.25s ease;
      }
      .mobile-drawer.is-open {
        max-height: 240px;
        border-bottom-color: var(--border);
      }
      .mobile-drawer-inner {
        padding: 1rem 1.25rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .drawer-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0.85rem;
        background: var(--surface-subtle);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
      }
      .drawer-link.logout {
        color: var(--danger);
        background: var(--danger-light);
      }

      main {
        padding: 1.5rem 1rem 2.5rem;
      }

      .banner-card {
        padding: 1.15rem;
      }
      .banner-actions {
        width: 100%;
      }
      .banner-actions .btn {
        flex: 1;
        min-height: 42px;
      }

      /* PCテーブルを隠し、モバイルカードリストを表示 */
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

    <!-- PCナビゲーション -->
    <div class="header-actions desktop-nav">
      <a href="/admin/email_settings.php" class="header-link">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="20" height="16" x="2" y="4" rx="2"></rect>
          <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
        </svg>
        <span>メール文面設定</span>
      </a>
      <a href="/admin/settings.php" class="header-link">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <span>アカウント設定</span>
      </a>
      <a href="/admin/login.php?action=logout" class="header-link logout">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" x2="9" y1="12" y2="12"></line>
        </svg>
        <span>ログアウト</span>
      </a>
    </div>

    <!-- スマホメニューボタン -->
    <button type="button" class="mobile-menu-btn" id="menuToggle" aria-label="メニューを開く" aria-expanded="false">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>
  </div>

  <!-- スマホ用ドロワー -->
  <div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-inner">
      <a href="/admin/email_settings.php" class="drawer-link">
        <span>メール文面設定</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
      </a>
      <a href="/admin/settings.php" class="drawer-link">
        <span>企業アカウント設定</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
      </a>
      <a href="/admin/login.php?action=logout" class="drawer-link logout">
        <span>ログアウト</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
      </a>
    </div>
  </div>
</header>

<main>
  <div class="page-title-row">
    <div class="page-title">
      <span>採用選考ダッシュボード</span>
      <span class="company-tag"><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> 様</span>
    </div>
  </div>

  <!-- エントリーURL & CSVエクスポート案内 -->
  <div class="banner-card">
    <div class="banner-info">
      <div class="banner-label">貴社専用エントリー受付URL</div>
      <div class="banner-url-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
          <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
        </svg>
        <span id="entryUrlText"><?= htmlspecialchars($selfEntryUrl, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>
    <div class="banner-actions">
      <button type="button" class="btn btn-secondary" onclick="copyEntryUrl()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
          <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
        </svg>
        <span>URLをコピー</span>
      </button>
      <a href="/admin/export_csv.php" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" x2="12" y1="15" y2="3"></line>
        </svg>
        <span>CSVエクスポート</span>
      </a>
    </div>
  </div>

  <!-- PC表示: テーブル -->
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>候補者氏名・連絡先</th>
          <th>選考ステータス</th>
          <th>提出書類</th>
          <th>選考アクション</th>
          <th>評価レポート</th>
          <th>エントリー日時</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($candidates)): ?>
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>現在登録されている候補者はいません。<br>上記の専用エントリーURLを募集要項等に設定してください。</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($candidates as $c): ?>
        <?php 
          $es = json_decode($c['entry_sheet_data'] ?? '{}', true);
          $interviewUrl = "{$baseUrl}/index.html?candidate_id={$c['id']}";
        ?>
        <tr>
          <td>
            <div class="candidate-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="candidate-email"><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?></div>
          </td>
          <td><?= getStatusBadge($c['interview_status']) ?></td>
          <td>
            <button type="button" class="btn btn-secondary" onclick="viewES(<?= htmlspecialchars(json_encode([
              'name' => $c['name'],
              'es'   => $es
            ]), ENT_QUOTES, 'UTF-8') ?>)">
              ESを確認
            </button>
          </td>
          <td>
            <?php if ($c['interview_status'] === 'applied'): ?>
              <form action="/admin/screening.php" method="POST" style="display:inline;" onsubmit="return confirm('書類選考を合格とし、候補者へ面接案内メールを送信しますか？');">
                <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="pass">
                <button type="submit" class="btn btn-success">合格 (面接案内)</button>
              </form>
              <form action="/admin/screening.php" method="POST" style="display:inline; margin-left: 4px;" onsubmit="return confirm('書類選考を見送りにしますか？');">
                <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="fail">
                <button type="submit" class="btn btn-danger">見送り</button>
              </form>
            <?php elseif ($c['interview_status'] === 'es_failed'): ?>
              <form action="/admin/screening.php" method="POST" style="display:inline;" onsubmit="return confirm('【誤操作の救済】\n見送りを取り消し、「お詫びと書類通過・面接案内メール」を候補者に再送信しますか？');">
                <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="repass">
                <button type="submit" class="btn btn-recover">選考復活 (再案内)</button>
              </form>
            <?php elseif ($c['interview_status'] === 'ready'): ?>
              <button type="button" class="btn btn-secondary" onclick="copyDirectUrl('<?= htmlspecialchars($interviewUrl, ENT_QUOTES, 'UTF-8') ?>')">
                面接URLを再コピー
              </button>
            <?php else: ?>
              <span style="color: var(--text-tertiary); font-size: 0.8rem;">対応完了</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($c['session_id']): ?>
              <a href="/admin/report.php?session_id=<?= urlencode((string)$c['session_id']) ?>" class="btn btn-primary">
                レポート閲覧
              </a>
            <?php else: ?>
              <span style="color: var(--text-tertiary); font-size: 0.8rem;">面接未完了</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="candidate-date"><?= htmlspecialchars(substr($c['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- スマホ表示: カードリスト -->
  <div class="mobile-cards-list">
    <?php if (empty($candidates)): ?>
      <div class="empty-state" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);">
        <p>現在登録されている候補者はいません。</p>
      </div>
    <?php endif; ?>

    <?php foreach ($candidates as $c): ?>
    <?php 
      $es = json_decode($c['entry_sheet_data'] ?? '{}', true);
      $interviewUrl = "{$baseUrl}/index.html?candidate_id={$c['id']}";
    ?>
    <div class="candidate-card">
      <div class="card-row-top">
        <div>
          <div class="candidate-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="candidate-email"><?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div><?= getStatusBadge($c['interview_status']) ?></div>
      </div>

      <div class="card-detail-group">
        <div class="detail-item">
          <span class="detail-label">エントリー日時</span>
          <span><?= htmlspecialchars(substr($c['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">評価レポート</span>
          <span>
            <?php if ($c['session_id']): ?>
              <a href="/admin/report.php?session_id=<?= urlencode((string)$c['session_id']) ?>" style="color:var(--brand-accent); font-weight:600; text-decoration:none;">閲覧可能 →</a>
            <?php else: ?>
              <span style="color:var(--text-tertiary);">未実施</span>
            <?php endif; ?>
          </span>
        </div>
      </div>

      <div class="card-actions-row">
        <button type="button" class="btn btn-secondary" onclick="viewES(<?= htmlspecialchars(json_encode([
          'name' => $c['name'],
          'es'   => $es
        ]), ENT_QUOTES, 'UTF-8') ?>)">
          ES確認
        </button>

        <?php if ($c['interview_status'] === 'applied'): ?>
          <form action="/admin/screening.php" method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('書類選考を合格とし、面接案内メールを送信しますか？');">
            <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="pass">
            <button type="submit" class="btn btn-success" style="width: 100%;">合格案内</button>
          </form>
          <form action="/admin/screening.php" method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('書類選考を見送りにしますか？');">
            <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="fail">
            <button type="submit" class="btn btn-danger" style="width: 100%;">見送り</button>
          </form>
        <?php elseif ($c['interview_status'] === 'es_failed'): ?>
          <form action="/admin/screening.php" method="POST" style="flex: 2; display: flex;" onsubmit="return confirm('見送りを取り消し、面接案内を送信しますか？');">
            <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="repass">
            <button type="submit" class="btn btn-recover" style="width: 100%;">選考を復活</button>
          </form>
        <?php elseif ($c['interview_status'] === 'ready'): ?>
          <button type="button" class="btn btn-secondary" style="flex: 2;" onclick="copyDirectUrl('<?= htmlspecialchars($interviewUrl, ENT_QUOTES, 'UTF-8') ?>')">
            面接URLをコピー
          </button>
        <?php elseif ($c['session_id']): ?>
          <a href="/admin/report.php?session_id=<?= urlencode((string)$c['session_id']) ?>" class="btn btn-primary" style="flex: 2;">
            レポート詳細を見る
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- ES詳細表示モーダル -->
<div class="modal-overlay" id="esModal" onclick="closeModalOnOverlay(event)">
  <div class="modal-content" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3 id="modalCandidateName">エントリーシート詳細</h3>
      <button type="button" class="modal-close-btn" onclick="closeModal()" aria-label="閉じる">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="es-section">
        <div class="es-title">自己PR</div>
        <div id="modalPR" class="es-body-text"></div>
      </div>
      <div class="es-section">
        <div class="es-title">学生時代に最も力を入れたこと（ガクチカ）</div>
        <div id="modalGakuchika" class="es-body-text"></div>
      </div>
      <div class="es-section">
        <div class="es-title">志望動機</div>
        <div id="modalMotivation" class="es-body-text"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal()">閉じる</button>
    </div>
  </div>
</div>

<!-- コピー完了トースト通知 -->
<div class="toast" id="toastMessage">クリップボードにコピーしました</div>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

<script>
  // メニュー開閉
  const toggleBtn = document.getElementById('menuToggle');
  const drawer = document.getElementById('mobileDrawer');
  if (toggleBtn && drawer) {
    toggleBtn.addEventListener('click', () => {
      const isOpen = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', String(!isOpen));
      toggleBtn.setAttribute('aria-label', isOpen ? 'メニューを開く' : 'メニューを閉じる');
      drawer.classList.toggle('is-open', !isOpen);
    });
  }

  // トースト表示関数
  function showToast(text) {
    const toast = document.getElementById('toastMessage');
    toast.textContent = text;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 2400);
  }

  // URLコピー処理
  function copyEntryUrl() {
    const url = document.getElementById('entryUrlText').textContent.trim();
    copyDirectUrl(url);
  }

  function copyDirectUrl(url) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(() => {
        showToast('URLをクリップボードにコピーしました');
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
      showToast('URLをクリップボードにコピーしました');
    } catch (err) {
      prompt('URLをコピーしてください:', text);
    }
    document.body.removeChild(ta);
  }

  // モーダル処理
  function viewES(data) {
    document.getElementById('modalCandidateName').textContent = data.name + ' 様 の提出書類';
    document.getElementById('modalPR').textContent = data.es.pr || '未記入';
    document.getElementById('modalGakuchika').textContent = data.es.gakuchika || '未記入';
    document.getElementById('modalMotivation').textContent = data.es.motivation || '未記入';
    document.getElementById('esModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('esModal').style.display = 'none';
  }

  function closeModalOnOverlay(e) {
    if (e.target.id === 'esModal') {
      closeModal();
    }
  }
</script>
</body>
</html>