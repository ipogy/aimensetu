<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/bootstrap.php';

$sessionId = $_GET['session_id'] ?? '';
if (!$sessionId) {
    die('session_id is required');
}

$pdo = getDb();
$companyId = $_SESSION['company_id'] ?? '';

// 自社候補者のセッションであることを確認
$stmt = $pdo->prepare("
    SELECT s.*, c.id AS candidate_id, c.name, c.email, c.final_decision, c.reviewer_comment, c.company_id
    FROM interview_sessions s 
    JOIN candidates c ON s.candidate_id = c.id 
    WHERE s.id = :id AND c.company_id = :cid
");
$stmt->execute(['id' => $sessionId, 'cid' => $companyId]);
$session = $stmt->fetch();

if (!$session) {
    die('面接データが見つかりません。アクセス権限をご確認ください。');
}

$summary   = json_decode($session['evaluation_summary'] ?? '{}', true);
$integrity = json_decode($session['integrity_log'] ?? '{}', true);

$stmtTurns = $pdo->prepare("SELECT * FROM interview_turns WHERE session_id = :sid ORDER BY turn_number ASC");
$stmtTurns->execute(['sid' => $sessionId]);
$turns = $stmtTurns->fetchAll();

$reloadCount = (int)($session['reload_count'] ?? 0);
$isSaved = isset($_GET['saved']) && $_GET['saved'] === '1';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>面接評価レポート | <?= htmlspecialchars($session['name'], ENT_QUOTES, 'UTF-8') ?> 様</title>
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
      max-width: 1040px;
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
      max-width: 1040px;
      width: 100%;
      margin: 0 auto;
      padding: 2.25rem 1.25rem 4rem;
    }

    .page-title-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .candidate-headline {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .page-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--text-primary);
      letter-spacing: -0.01em;
    }

    .candidate-sub {
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    /* 共通カード */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
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
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    .card-title svg {
      color: var(--text-secondary);
    }

    /* アラート・バナー */
    .alert-success {
      background: var(--success-light);
      border: 1px solid var(--success-border);
      color: var(--success);
      padding: 0.85rem 1.15rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.25rem;
      font-weight: 600;
      font-size: 0.88rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .guideline-banner {
      background: var(--brand-light);
      border: 1px solid #bfdbfe;
      border-radius: var(--radius);
      padding: 1rem 1.25rem;
      margin-bottom: 1.5rem;
      display: flex;
      gap: 0.75rem;
      align-items: flex-start;
      font-size: 0.85rem;
      color: #1e40af;
      line-height: 1.6;
    }

    .guideline-banner svg {
      flex-shrink: 0;
      margin-top: 0.2rem;
    }

    /* ステータスタグ */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.3rem 0.65rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-clean {
      background: var(--success-light);
      color: var(--success);
      border: 1px solid var(--success-border);
    }

    .badge-warn {
      background: var(--warning-light);
      color: var(--warning);
      border: 1px solid var(--warning-border);
    }

    /* 監査・インテグリティグリッド */
    .integrity-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 0.85rem;
      margin-top: 0.85rem;
    }

    .integrity-cell {
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0.9rem;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }

    .integrity-label {
      font-size: 0.76rem;
      font-weight: 700;
      color: var(--text-secondary);
    }

    .integrity-val-wrap {
      display: flex;
      align-items: baseline;
      gap: 0.4rem;
    }

    .integrity-val {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--text-primary);
      font-feature-settings: "tnum";
    }

    .integrity-sub {
      font-size: 0.75rem;
      color: var(--text-tertiary);
    }

    .integrity-status-note {
      font-size: 0.75rem;
      font-weight: 600;
      margin-top: 0.15rem;
    }

    .status-ok {
      color: var(--success);
    }

    .status-alert {
      color: var(--danger);
    }

    .callout-box {
      margin-top: 1rem;
      background: var(--warning-light);
      border: 1px solid var(--warning-border);
      border-radius: var(--radius-sm);
      padding: 0.85rem 1rem;
      font-size: 0.82rem;
      color: #92400e;
      line-height: 1.55;
    }

    /* AI総合スコア */
    .score-hero {
      display: flex;
      align-items: center;
      gap: 2rem;
      padding: 0.75rem 0 1.25rem;
      flex-wrap: wrap;
    }

    .score-block {
      display: flex;
      flex-direction: column;
      border-right: 1px solid var(--border);
      padding-right: 2rem;
    }

    .score-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .score-value {
      font-size: 2.75rem;
      font-weight: 800;
      color: var(--brand-accent);
      line-height: 1;
      margin-top: 0.25rem;
      font-feature-settings: "tnum";
    }

    .score-denom {
      font-size: 1rem;
      color: var(--text-tertiary);
      font-weight: 600;
    }

    .feedback-block {
      flex: 1;
      min-width: 260px;
    }

    .feedback-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 0.35rem;
    }

    .feedback-text {
      font-size: 0.92rem;
      color: var(--text-secondary);
      line-height: 1.65;
    }

    /* 強み・懸念点グリッド */
    .evaluation-aspects {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      border-top: 1px solid var(--border);
      padding-top: 1.25rem;
    }

    .aspect-column {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .aspect-heading {
      font-size: 0.85rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .aspect-heading.positive {
      color: var(--success);
    }

    .aspect-heading.negative {
      color: var(--danger);
    }

    .aspect-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
    }

    .aspect-list li {
      position: relative;
      padding-left: 1.1rem;
      font-size: 0.85rem;
      color: var(--text-secondary);
      line-height: 1.5;
    }

    .aspect-list li::before {
      content: "";
      position: absolute;
      left: 0.2rem;
      top: 0.55rem;
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: var(--text-tertiary);
    }

    /* 判定フォーム */
    .decision-row {
      display: grid;
      grid-template-columns: 240px 1fr;
      gap: 1.5rem;
      align-items: start;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.4rem;
    }

    select {
      width: 100%;
      height: 42px;
      padding: 0 0.85rem;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      background: var(--surface);
      color: var(--text-primary);
      font-family: inherit;
      cursor: pointer;
    }

    select:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      user-select: none;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-top: 0.75rem;
    }

    .checkbox-label input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--brand);
      cursor: pointer;
    }

    textarea {
      width: 100%;
      height: 100px;
      padding: 0.75rem 0.85rem;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      font-size: 0.88rem;
      line-height: 1.6;
      background: var(--surface);
      color: var(--text-primary);
      font-family: inherit;
      resize: vertical;
    }

    textarea:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .btn-submit {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      background: var(--brand);
      color: #fff;
      border: 1px solid transparent;
      padding: 0.7rem 1.5rem;
      min-height: 42px;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: background-color 0.15s ease;
      font-family: inherit;
    }

    .btn-submit:hover {
      background: var(--brand-hover);
    }

    .btn-submit:active {
      transform: translateY(1px);
    }

    /* 対話ターン */
    .turn-timeline {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .turn-card {
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      overflow: hidden;
    }

    .turn-header {
      background: var(--surface-subtle);
      padding: 0.75rem 1rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.5rem;
    }

    .turn-tag {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .turn-category {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--brand-accent);
      background: var(--brand-light);
      padding: 0.15rem 0.5rem;
      border-radius: 4px;
      border: 1px solid #bfdbfe;
    }

    .turn-content {
      padding: 1rem 1.15rem;
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
    }

    .speaker-row {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .speaker-name {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-tertiary);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .speaker-text {
      font-size: 0.9rem;
      color: var(--text-primary);
      line-height: 1.6;
    }

    .audio-player-wrap {
      margin-top: 0.25rem;
    }

    audio {
      width: 100%;
      height: 38px;
    }

    .meta-metrics {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      padding-top: 0.75rem;
      border-top: 1px solid var(--border);
    }

    .metric-chip {
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      padding: 0.25rem 0.6rem;
      border-radius: 4px;
      font-size: 0.78rem;
      color: var(--text-secondary);
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-weight: 500;
    }

    .metric-chip strong {
      color: var(--text-primary);
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

    /* レスポンシブ最適化 (768px以下) */
    @media (max-width: 768px) {
      main {
        padding: 1.5rem 1rem 3rem;
      }
      .page-title {
        font-size: 1.25rem;
      }
      .card {
        padding: 1.15rem;
      }
      .score-block {
        border-right: none;
        padding-right: 0;
        width: 100%;
      }
      .evaluation-aspects {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      .decision-row {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      .btn-submit {
        width: 100%;
        min-height: 46px;
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
      <span>候補者一覧へ戻る</span>
    </a>
  </div>
</header>

<main>
  <div class="page-title-row">
    <div class="candidate-headline">
      <h1 class="page-title"><?= htmlspecialchars($session['name'], ENT_QUOTES, 'UTF-8') ?> 様 の面接評価レポート</h1>
      <div class="candidate-sub"><?= htmlspecialchars($session['email'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
      <?php if ($reloadCount > 0): ?>
        <span class="badge badge-warn">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          面接再開ログ: <?= $reloadCount ?>回
        </span>
      <?php else: ?>
        <span class="badge badge-clean">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          中断・リロードなし
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isSaved): ?>
    <div class="alert-success" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <span>合否判定および社内メモを正常に更新・保存しました。</span>
    </div>
  <?php endif; ?>

  <div class="guideline-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="10"/>
      <line x1="12" y1="16" x2="12" y2="12"/>
      <line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
    <div>
      <strong>選考運用に関する確認事項</strong><br>
      本画面に表示されるAI評価スコアおよび不正監視メトリクスは、一次選考の判定支援を目的とした参考値です。通信環境の一時的な不安定や端末ハードウェア仕様による誤検知の可能性を勘案し、最終判定は対話音声の傾聴および提出書類を統合して実施してください。
    </div>
  </div>

  <!-- 受検環境 & 不正監視インテグリティカード -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <span>受検環境および整合性監査ログ（インテグリティ判定）</span>
      </h2>
      <span style="font-size:0.75rem; color:var(--text-tertiary);">
        規約同意: <?= !empty($integrity['consentTimestamp']) ? htmlspecialchars((string)$integrity['consentTimestamp'], ENT_QUOTES, 'UTF-8') : '未記録' ?>
      </span>
    </div>

    <?php if ($integrity): ?>
      <div class="integrity-grid">
        <!-- 視線・顔向き逸脱 -->
        <div class="integrity-cell">
          <div class="integrity-label">視線・顔向き逸脱率</div>
          <?php $gazeRatio = (int)($integrity['gazeDeviationRatio'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $gazeRatio ?>%</span>
            <span class="integrity-sub">(累計 <?= (int)($integrity['gazeAwayDurationSec'] ?? 0) ?>秒)</span>
          </div>
          <div class="integrity-status-note <?= $gazeRatio >= 35 ? 'status-alert' : 'status-ok' ?>">
            <?= $gazeRatio >= 35 ? '逸脱検知あり (カンペ・外部画面参照の疑い)' : '正面回答を概ね維持' ?>
          </div>
        </div>

        <!-- 顔消失・フレームアウト -->
        <div class="integrity-cell">
          <div class="integrity-label">フレームアウト（離席・消失）</div>
          <?php $lostCount = (int)($integrity['faceLostCount'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $lostCount ?>回</span>
            <span class="integrity-sub">(<?= (int)($integrity['faceLostDurationSec'] ?? 0) ?>秒)</span>
          </div>
          <div class="integrity-status-note <?= $lostCount > 0 ? 'status-alert' : 'status-ok' ?>">
            <?= $lostCount > 0 ? 'カメラ枠外への離脱を検知' : 'カメラ枠内を常時維持' ?>
          </div>
        </div>

        <!-- 複数人映り込み -->
        <div class="integrity-cell">
          <div class="integrity-label">複数人検出（第三者補助検知）</div>
          <?php $multiFaces = (int)($integrity['multipleFacesCount'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $multiFaces ?>回</span>
          </div>
          <div class="integrity-status-note <?= $multiFaces > 0 ? 'status-alert' : 'status-ok' ?>">
            <?= $multiFaces > 0 ? '複数人物の映り込みを検知' : '単独受検を確認' ?>
          </div>
        </div>

        <!-- タブ切り替え -->
        <div class="integrity-cell">
          <div class="integrity-label">ウィンドウ離脱（タブ切替）</div>
          <?php $blurCount = (int)($integrity['blurCount'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $blurCount ?>回</span>
            <span class="integrity-sub">(離脱 <?= (int)($integrity['tabAwayDurationSec'] ?? 0) ?>秒)</span>
          </div>
          <div class="integrity-status-note <?= $blurCount > 0 ? 'status-alert' : 'status-ok' ?>">
            <?= $blurCount > 0 ? '別ウィンドウへの切替を記録' : '画面フォーカス維持' ?>
          </div>
        </div>

        <!-- フルスクリーン解除 -->
        <div class="integrity-cell">
          <div class="integrity-label">フルスクリーンモード解除</div>
          <?php $fsExit = (int)($integrity['fullscreenExitCount'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $fsExit ?>回</span>
          </div>
          <div class="integrity-status-note <?= $fsExit > 0 ? 'status-alert' : 'status-ok' ?>">
            <?= $fsExit > 0 ? '全画面表示の解除を検知' : '全画面表示を維持' ?>
          </div>
        </div>

        <!-- 仮想カメラ -->
        <div class="integrity-cell">
          <div class="integrity-label">仮想カメラソフトウェア</div>
          <?php $isVcam = !empty($integrity['isVirtualCamera']); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $isVcam ? '検知あり' : '未検知' ?></span>
          </div>
          <div class="integrity-status-note <?= $isVcam ? 'status-alert' : 'status-ok' ?>">
            <?= $isVcam ? '仮想デバイス接続を確認' : '実機カメラ経由' ?>
          </div>
        </div>

        <!-- マルチモニター -->
        <div class="integrity-cell">
          <div class="integrity-label">複数モニター接続環境</div>
          <?php $isMulti = !empty($integrity['isMultiMonitor']); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $isMulti ? '接続中' : '1画面' ?></span>
          </div>
          <div class="integrity-status-note <?= $isMulti ? 'status-alert' : 'status-ok' ?>">
            <?= $isMulti ? 'サブディスプレイ接続あり' : '単一モニター受検' ?>
          </div>
        </div>

        <!-- コピペ試行 -->
        <div class="integrity-cell">
          <div class="integrity-label">クリップボード貼付試行</div>
          <?php $pasteCount = (int)($integrity['pasteAttemptCount'] ?? 0); ?>
          <div class="integrity-val-wrap">
            <span class="integrity-val"><?= $pasteCount ?>回</span>
          </div>
          <div class="integrity-status-note <?= $pasteCount > 0 ? 'status-alert' : 'status-ok' ?>">
            <?= $pasteCount > 0 ? 'ペースト操作の試行を遮断' : '操作履歴なし' ?>
          </div>
        </div>
      </div>

      <div class="callout-box">
        <strong>監査ガイドライン：</strong><br>
        「視線逸脱率35%以上」または「タブ離脱3回以上」が記録されている場合は、該当ターンの音声を試聴し、棒読みや不自然な長考の有無を確認してください。
      </div>
    <?php else: ?>
      <p style="color: var(--text-tertiary); font-size: 0.88rem; padding: 1rem 0;">受検中の監査ログは記録されていません。</p>
    <?php endif; ?>
  </section>

  <!-- AI 総合評価サマリー -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <span>AI総合評価サマリー</span>
      </h2>
    </div>

    <?php if ($summary): ?>
      <div class="score-hero">
        <div class="score-block">
          <span class="score-label">総合スコア</span>
          <div class="score-value">
            <?= htmlspecialchars((string)($summary['overall_score'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <span class="score-denom">/ 100</span>
          </div>
        </div>
        <div class="feedback-block">
          <div class="feedback-title">評価総括・フィードバック</div>
          <p class="feedback-text"><?= nl2br(htmlspecialchars((string)($summary['recommendation'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
      </div>

      <div class="evaluation-aspects">
        <div class="aspect-column">
          <div class="aspect-heading positive">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span>高く評価された強み</span>
          </div>
          <ul class="aspect-list">
            <?php foreach (($summary['strengths'] ?? []) as $s): ?>
              <li><?= htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="aspect-column">
          <div class="aspect-heading negative">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>確認された課題・懸念事項</span>
          </div>
          <ul class="aspect-list">
            <?php foreach (($summary['weaknesses'] ?? []) as $w): ?>
              <li><?= htmlspecialchars((string)$w, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php else: ?>
      <p style="color: var(--text-tertiary); font-size: 0.88rem; padding: 1rem 0;">評価データが集計中、または面接が完了していません。</p>
    <?php endif; ?>
  </section>

  <!-- 最終判定・社内コメント入力 -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="8.5" cy="7.5" r="4"/>
          <polyline points="17 11 19 13 23 9"/>
        </svg>
        <span>採用選考 最終判定</span>
      </h2>
    </div>

    <form action="judge.php" method="POST" onsubmit="return confirm('合否判定および社内コメントを保存しますか？');">
      <input type="hidden" name="candidate_id" value="<?= htmlspecialchars((string)$session['candidate_id'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="session_id" value="<?= htmlspecialchars((string)$session['id'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="decision-row">
        <div>
          <div class="form-group">
            <label for="final_decision">合否ステータス判定</label>
            <select id="final_decision" name="final_decision">
              <option value="unreviewed" <?= ($session['final_decision'] ?? '') === 'unreviewed' ? 'selected' : '' ?>>未審査（未判定）</option>
              <option value="pass" <?= ($session['final_decision'] ?? '') === 'pass' ? 'selected' : '' ?>>合格 (Pass)</option>
              <option value="fail" <?= ($session['final_decision'] ?? '') === 'fail' ? 'selected' : '' ?>>不合格 (Fail)</option>
              <option value="hold" <?= ($session['final_decision'] ?? '') === 'hold' ? 'selected' : '' ?>>保留 (Hold)</option>
            </select>
          </div>

          <label class="checkbox-label">
            <input type="checkbox" name="send_notification_email" value="1">
            <span>保存と同時に通知メールを送信</span>
          </label>
        </div>

        <div>
          <div class="form-group" style="margin-bottom: 0.75rem;">
            <label for="reviewer_comment">面接官コメント・社内共有メモ (候補者非公開)</label>
            <textarea id="reviewer_comment" name="reviewer_comment" placeholder="次回面接での深掘りポイントや評価所感を記入"><?= htmlspecialchars((string)($session['reviewer_comment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <button type="submit" class="btn-submit">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            <span>判定結果を確定・保存</span>
          </button>
        </div>
      </div>
    </form>
  </section>

  <!-- 対話ターンログ & 音声 -->
  <section class="card">
    <div class="card-header-line">
      <h2 class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>対話履歴および回答音声アーカイブ</span>
      </h2>
      <span style="font-size:0.75rem; color:var(--text-tertiary);">全 <?= count($turns) ?> ターン記録</span>
    </div>

    <div class="turn-timeline">
      <?php foreach ($turns as $t): ?>
        <?php $score = json_decode($t['turn_score'] ?? '{}', true); ?>
        <div class="turn-card">
          <div class="turn-header">
            <span class="turn-tag">ターン <?= htmlspecialchars((string)$t['turn_number'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="turn-category"><?= htmlspecialchars((string)$t['question_type'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>

          <div class="turn-content">
            <div class="speaker-row">
              <span class="speaker-name">AI面接官 質問</span>
              <p class="speaker-text"><?= htmlspecialchars((string)$t['question_text'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="speaker-row">
              <span class="speaker-name">候補者 回答</span>
              <p class="speaker-text"><?= htmlspecialchars((string)($t['answer_text'] ?? '（無回答または音声認識不可）'), ENT_QUOTES, 'UTF-8') ?></p>

              <?php if (!empty($t['answer_audio_filename'])): ?>
                <div class="audio-player-wrap">
                  <audio controls preload="none" src="/audio.php?type=uploads&file=<?= urlencode((string)$t['answer_audio_filename']) ?>&session_id=<?= urlencode((string)$sessionId) ?>"></audio>
                </div>
              <?php endif; ?>
            </div>

            <div class="meta-metrics">
              <?php if ($t['duration_sec'] !== null): ?>
                <div class="metric-chip">
                  <span>回答所要時間:</span>
                  <strong><?= (int)$t['duration_sec'] ?> 秒</strong>
                </div>
              <?php endif; ?>
              <?php if ($score): ?>
                <div class="metric-chip">
                  <span>論理性:</span>
                  <strong><?= htmlspecialchars((string)($score['logic_score'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / 5</strong>
                </div>
                <div class="metric-chip">
                  <span>具体性:</span>
                  <strong><?= htmlspecialchars((string)($score['concreteness_score'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / 5</strong>
                </div>
                <?php if (!empty($score['note'])): ?>
                  <div class="metric-chip" style="flex: 1 1 100%;">
                    <span>メモ:</span>
                    <span><?= htmlspecialchars((string)$score['note'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>