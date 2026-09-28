<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/bootstrap.php';

$companyId = $_SESSION['company_id'];
$pdo = getDb();

$message = '';
$error = '';

// 企業情報の取得
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $companyId]);
$company = $stmt->fetch();

if (!$company) {
    die('企業アカウントが見つかりません。');
}

$types = [
    'entry_received' => [
        'title' => 'エントリー受付完了通知',
        'desc'  => '候補者がエントリーシート（応募フォーム）を送信した直後に自動送信されます。',
    ],
    'screening_pass' => [
        'title' => '書類選考通過・面接案内通知',
        'desc'  => '管理画面で書類選考を「合格」にした際に送信されます。※ {interview_url} を必ず本文内に含めてください。',
    ],
    'screening_fail' => [
        'title' => '書類選考結果通知（見送り）',
        'desc'  => '管理画面で書類選考を「見送り」にした際に送信されます。',
    ],
    'interview_pass' => [
        'title' => '面接選考通過・次回選考案内通知',
        'desc'  => '面接レポート画面で合否判定を「合格」として保存・送信した際に送信されます。',
    ],
];

// 保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['templates'] ?? [];
    $cleanTemplates = [];

    foreach ($types as $key => $meta) {
        $cleanTemplates[$key] = [
            'subject' => trim($submitted[$key]['subject'] ?? ''),
            'body'    => trim($submitted[$key]['body'] ?? ''),
        ];
    }

    try {
        $stmtUpdate = $pdo->prepare("UPDATE companies SET email_templates = :tpl WHERE id = :id");
        $stmtUpdate->execute([
            'tpl' => json_encode($cleanTemplates, JSON_UNESCAPED_UNICODE),
            'id'  => $companyId
        ]);
        $company['email_templates'] = json_encode($cleanTemplates, JSON_UNESCAPED_UNICODE);
        $message = 'メールテンプレートの設定を正常に保存しました。';
    } catch (PDOException $e) {
        $error = '保存エラー: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>メールテンプレート設定 | <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></title>
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

    /* メインコンテンツ */
    main {
      flex: 1;
      max-width: 1040px;
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

    /* 置換タグ説明ボックス */
    .variables-guide {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.15rem 1.25rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-sm);
    }

    .variables-title {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.65rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .tag-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .tag-chip {
      background: var(--surface-subtle);
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 0.3rem 0.6rem;
      font-size: 0.82rem;
      color: var(--text-primary);
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }

    .tag-chip code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: var(--brand-accent);
      font-weight: 600;
      font-size: 0.85rem;
    }

    .tag-chip .chip-desc {
      color: var(--text-secondary);
      font-size: 0.78rem;
    }

    /* テンプレートカード群 */
    .template-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: var(--shadow-sm);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .template-card:focus-within {
      border-color: var(--brand-accent);
      box-shadow: var(--shadow-md);
    }

    .template-header {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      margin-bottom: 1.25rem;
    }

    .template-index {
      width: 24px;
      height: 24px;
      background: var(--surface-subtle);
      color: var(--text-secondary);
      border: 1px solid var(--border);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      flex-shrink: 0;
      margin-top: 0.15rem;
    }

    .template-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .template-desc {
      font-size: 0.82rem;
      color: var(--text-secondary);
      margin-top: 0.2rem;
      line-height: 1.5;
    }

    .form-group {
      margin-bottom: 1.15rem;
    }

    .form-group:last-child {
      margin-bottom: 0;
    }

    label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.4rem;
    }

    input[type="text"] {
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

    textarea {
      width: 100%;
      min-height: 150px;
      padding: 0.75rem 0.85rem;
      border: 1px solid var(--border-strong);
      border-radius: var(--radius-sm);
      font-size: 0.88rem;
      line-height: 1.6;
      background: var(--surface);
      color: var(--text-primary);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
      font-family: inherit;
      resize: vertical;
    }

    input[type="text"]:focus,
    textarea:focus {
      outline: none;
      border-color: var(--brand-accent);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* ボトムアクション */
    .submit-container {
      display: flex;
      justify-content: flex-end;
      padding-top: 1rem;
    }

    .btn-submit {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      background: var(--brand);
      color: #fff;
      border: 1px solid transparent;
      padding: 0.75rem 2rem;
      min-height: 46px;
      font-size: 0.92rem;
      font-weight: 600;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: background-color 0.15s ease;
      user-select: none;
      box-shadow: var(--shadow-sm);
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
        padding: 1.5rem 1rem 5.5rem; /* スマホ固定バーの余白 */
      }
      .template-card {
        padding: 1.15rem;
      }
      input[type="text"] {
        height: 44px;
      }

      /* スマホ固定保存バー */
      .submit-container {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-top: 1px solid var(--border);
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom));
        z-index: 20;
        box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.05);
      }
      .btn-submit {
        width: 100%;
        min-height: 48px;
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
    <h1 class="page-title">メール文面・テンプレート設定</h1>
    <p class="page-desc">各選考フェーズで候補者へ自動配信されるメッセージを貴社の方針に合わせて調整します。</p>
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

  <div class="variables-guide">
    <div class="variables-title">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12.01" y2="8"></line>
      </svg>
      <span>使用可能な自動置換変数</span>
    </div>
    <div class="tag-chips">
      <div class="tag-chip">
        <code>{candidate_name}</code>
        <span class="chip-desc">候補者氏名</span>
      </div>
      <div class="tag-chip">
        <code>{company_name}</code>
        <span class="chip-desc">貴社名</span>
      </div>
      <div class="tag-chip">
        <code>{interview_url}</code>
        <span class="chip-desc">候補者専用面接URL（面接案内用）</span>
      </div>
    </div>
  </div>

  <form method="POST">
    <?php 
      $idx = 1;
      foreach ($types as $key => $meta): 
        $tpl = getCompanyEmailTemplate($company, $key);
    ?>
      <div class="template-card">
        <div class="template-header">
          <span class="template-index"><?= $idx ?></span>
          <div>
            <div class="template-title"><?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="template-desc"><?= htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>

        <div class="form-group">
          <label for="tpl_<?= $key ?>_subject">メール件名</label>
          <input type="text" id="tpl_<?= $key ?>_subject" name="templates[<?= $key ?>][subject]" value="<?= htmlspecialchars($tpl['subject'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="form-group">
          <label for="tpl_<?= $key ?>_body">メール本文</label>
          <textarea id="tpl_<?= $key ?>_body" name="templates[<?= $key ?>][body]" required><?= htmlspecialchars($tpl['body'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>
    <?php 
      $idx++;
      endforeach; 
    ?>

    <div class="submit-container">
      <button type="submit" class="btn-submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>テンプレート設定を保存する</span>
      </button>
    </div>
  </form>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>