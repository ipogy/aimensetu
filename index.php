<?php
declare(strict_types=1);
require_once __DIR__ . '/api/bootstrap.php';

$baseUrl = rtrim($_ENV['APP_BASE_URL'] ?? 'https://ai-mensetu.ipogy.org', '/');
$testEntryUrl = "{$baseUrl}/entry.html?company=test";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>AI面接選考システム | 採用DXプラットフォーム</title>
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
      
      --brand: #1e3a8a;
      --brand-hover: #172554;
      --brand-light: #eff6ff;
      --brand-accent: #2563eb;
      
      --status-active: #059669;
      --status-active-bg: #ecfdf5;

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
      z-index: 40;
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

    /* PC ナビゲーション */
    .header-actions.desktop-nav {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    /* スマホ用メニューボタン */
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

    /* スマホ用ドロワーメニュー */
    .mobile-drawer {
      display: none;
    }

    /* 共通ボタン定義 */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 600;
      padding: 0.6rem 1rem;
      min-height: 42px;
      border-radius: var(--radius-sm);
      border: 1px solid transparent;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.15s ease, border-color 0.15s ease;
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
    }

    .btn-subtle {
      background: transparent;
      color: var(--text-secondary);
    }
    .btn-subtle:hover {
      color: var(--text-primary);
      background: var(--surface-subtle);
    }

    /* メインレイアウト */
    main {
      flex: 1;
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      padding: 3rem 1.25rem 4rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3.5rem;
      align-items: start;
    }

    /* 左カラム: 概要と諸元 */
    .hero-panel {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: var(--status-active-bg);
      border: 1px solid rgba(5, 150, 105, 0.2);
      padding: 0.3rem 0.65rem;
      border-radius: var(--radius-sm);
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--status-active);
      width: fit-content;
    }

    .status-badge::before {
      content: "";
      width: 6px;
      height: 6px;
      background: var(--status-active);
      border-radius: 50%;
    }

    .hero-heading {
      font-size: 2.1rem;
      font-weight: 800;
      line-height: 1.3;
      letter-spacing: -0.02em;
      color: var(--text-primary);
    }

    .hero-lead {
      font-size: 0.975rem;
      color: var(--text-secondary);
      line-height: 1.7;
    }

    .spec-group {
      margin-top: 0.5rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
    }

    .spec-row {
      display: flex;
      padding: 0.85rem 1.15rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.85rem;
    }
    .spec-row:last-child {
      border-bottom: none;
    }

    .spec-term {
      width: 140px;
      font-weight: 600;
      color: var(--text-secondary);
      flex-shrink: 0;
    }

    .spec-desc {
      color: var(--text-primary);
      font-weight: 500;
    }

    /* 右カラム: ポータルアクセス */
    .portal-panel {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .panel-heading {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .access-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 1.15rem;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .access-card:hover {
      border-color: var(--border-strong);
      box-shadow: var(--shadow-md);
    }

    .access-card.featured {
      border-color: var(--brand-accent);
    }

    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 0.5rem;
    }

    .card-label {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .card-tag {
      font-size: 0.72rem;
      font-weight: 600;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      background: var(--brand-light);
      color: var(--brand-accent);
    }

    .card-summary {
      font-size: 0.875rem;
      color: var(--text-secondary);
      line-height: 1.6;
    }

    .card-checklist {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      font-size: 0.82rem;
      color: var(--text-secondary);
      padding: 0.85rem 0 0;
      border-top: 1px solid var(--border);
    }

    .card-checklist li {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .card-checklist li::before {
      content: "";
      width: 4px;
      height: 4px;
      background: var(--text-tertiary);
      border-radius: 50%;
    }

    .access-card .btn {
      width: 100%;
      min-height: 44px;
    }

    /* フッター */
    footer {
      background: var(--surface);
      border-top: 1px solid var(--border);
      padding: 2rem 1.25rem calc(2rem + env(safe-area-inset-bottom));
      margin-top: auto;
    }

    .footer-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.82rem;
      color: var(--text-tertiary);
    }

    .footer-nav {
      display: flex;
      gap: 1.5rem;
    }

    .footer-nav a {
      color: var(--text-secondary);
      text-decoration: none;
    }
    .footer-nav a:hover {
      color: var(--text-primary);
    }

    .mobile-cta-bar {
      display: none;
    }

    /* レスポンシブ (900px以下) */
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
        max-height: 220px;
        border-bottom-color: var(--border);
      }

      .mobile-drawer-inner {
        padding: 1rem 1.25rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }

      .drawer-title {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
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

      .drawer-link.subtle {
        background: transparent;
        border: 1px dashed var(--border-strong);
        color: var(--text-secondary);
        font-size: 0.82rem;
      }

      main {
        grid-template-columns: 1fr;
        gap: 2.5rem;
        padding-top: 1.75rem;
        padding-bottom: 2.5rem;
      }

      .hero-heading {
        font-size: 1.65rem;
      }

      .spec-row {
        flex-direction: column;
        gap: 0.2rem;
      }
      .spec-term {
        width: 100%;
        font-size: 0.78rem;
      }

      /* 固定CTAバー */
      .mobile-cta-bar {
        display: block;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-top: 1px solid var(--border);
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom));
        z-index: 30;
      }

      .mobile-cta-bar .btn {
        width: 100%;
        min-height: 48px;
        font-size: 0.95rem;
      }

      /* 固定バーの被り解消: 固定バー高さ分(約68px)をフッターのパディング下部に確保 */
      footer {
        padding-top: 1.75rem;
        padding-bottom: calc(5rem + env(safe-area-inset-bottom));
      }

      .footer-inner {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
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

    <!-- PC専用ナビゲーション -->
    <div class="header-actions desktop-nav">
      <a href="/admin/login.php" class="btn btn-secondary">企業ログイン</a>
      <a href="/super-admin" class="btn btn-subtle">統括管理</a>
    </div>

    <!-- スマホ専用メニュー開閉ボタン -->
    <button type="button" class="mobile-menu-btn" id="menuToggle" aria-label="管理メニューを開く" aria-expanded="false">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>
  </div>

  <!-- スマホ用ドロワーメニュー -->
  <div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-inner">
      <div class="drawer-title">管理者アクセス</div>
      <a href="/admin/login.php" class="drawer-link">
        <span>企業管理ログイン</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
      </a>
      <a href="/super-admin" class="drawer-link subtle">
        <span>システム統括コンソール</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
      </a>
    </div>
  </div>
</header>

<main>
  <!-- 左カラム: サービス概要 & 稼働情報 -->
  <section class="hero-panel">
    <div class="status-badge">選考対話エンジン 稼働中</div>
    <h1 class="hero-heading">公平で再現性の高い<br>オンライン対話選考を実現</h1>
    <p class="hero-lead">
      事前の応募シート登録から、Webブラウザのみで完結する音声インタビュー、自動文字起こし、評価サマリーの構造化までを円滑に接続する選考インフラです。
    </p>

    <div class="spec-group">
      <div class="spec-row">
        <div class="spec-term">面接インターフェース</div>
        <div class="spec-desc">双方向リアルタイム音声対話</div>
      </div>
      <div class="spec-row">
        <div class="spec-term">動作環境</div>
        <div class="spec-desc">Webブラウザ標準準拠（端末アプリ導入不要）</div>
      </div>
      <div class="spec-row">
        <div class="spec-term">整合性監査</div>
        <div class="spec-desc">視線・ウィンドウ状態検知 / 全文ログ記録</div>
      </div>
      <div class="spec-row">
        <div class="spec-term">データ出力</div>
        <div class="spec-desc">選考ログCSV / 個別評価シートエクスポート</div>
      </div>
    </div>
  </section>

  <!-- 右カラム: 目的別エントランス -->
  <section class="portal-panel">
    <div class="panel-heading">ポータル選択</div>

    <!-- 候補者ポータル -->
    <div class="access-card featured">
      <div class="card-top">
        <div class="card-label">候補者・受検者ポータル</div>
        <span class="card-tag">動作テスト可能</span>
      </div>
      <p class="card-summary">
        所要時間やデバイスの適合性を確認した上で、本番同様の対話型インタビューをテスト受検できます。
      </p>
      <ul class="card-checklist">
        <li>マイクおよびカメラの疎通確認</li>
        <li>回答制限タイマーに沿った音声入力</li>
        <li>提出データに基づく個別質問の提示</li>
      </ul>
      <a href="<?= htmlspecialchars($testEntryUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        テスト受検を開始する
      </a>
    </div>

    <!-- 企業採用担当者 -->
    <div class="access-card">
      <div class="card-top">
        <div class="card-label">採用担当者コンソール</div>
      </div>
      <p class="card-summary">
        応募者管理、面接録音データの再生、全文テキストログの照合、評価サマリーの閲覧および合否ステータスの更新を行います。
      </p>
      <ul class="card-checklist">
        <li>専用エントリーURLの発行・管理</li>
        <li>録音データおよび文字起こしの確認</li>
        <li>選考結果の一括CSVダウンロード</li>
      </ul>
      <a href="/admin/login.php" class="btn btn-secondary">
        企業管理画面へログイン
      </a>
    </div>

    <!-- 統括管理 -->
    <div class="access-card">
      <div class="card-top">
        <div class="card-label">システム統括コンソール</div>
      </div>
      <p class="card-summary">
        利用企業アカウントの発行・停止、テナントごとの応募動向モニタリング、および基盤パラメータの保守を行います。
      </p>
      <ul class="card-checklist">
        <li>テナント契約・権限の管理</li>
        <li>システム全体のログ・ステータス監視</li>
      </ul>
      <a href="/super-admin" class="btn btn-secondary">
        統括コンソールへ
      </a>
    </div>
  </section>
</main>

<!-- スマホ向け固定CTAバー -->
<div class="mobile-cta-bar">
  <a href="<?= htmlspecialchars($testEntryUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
    テスト受検エントリーを開始する
  </a>
</div>

<footer>
  <div class="footer-inner">
    <div>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</div>
    <div class="footer-nav">
      <a href="/admin/login.php">企業ログイン</a>
      <a href="/super-admin">統括管理</a>
    </div>
  </div>
</footer>

<script>
  const toggleBtn = document.getElementById('menuToggle');
  const drawer = document.getElementById('mobileDrawer');

  if (toggleBtn && drawer) {
    toggleBtn.addEventListener('click', () => {
      const isOpen = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', String(!isOpen));
      toggleBtn.setAttribute('aria-label', isOpen ? '管理メニューを開く' : '管理メニューを閉じる');
      drawer.classList.toggle('is-open', !isOpen);
    });
  }
</script>

</body>
</html>