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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI面接選考システム | 次世代型対話式採用プラットフォーム</title>
  <style>
    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --slate-900: #0f172a;
      --slate-800: #1e293b;
      --slate-600: #475569;
      --slate-100: #f1f5f9;
      --border: #e2e8f0;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; color: var(--slate-800); background: #f8fafc; line-height: 1.6; }
    
    header { background: #fff; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; }
    .header-inner { max-width: 1140px; margin: 0 auto; padding: 0.9rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .logo { font-weight: 800; font-size: 1.25rem; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
    .header-nav { display: flex; gap: 1rem; align-items: center; }

    .btn { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 600; font-size: 0.88rem; padding: 0.55rem 1.1rem; border-radius: 6px; transition: all 0.2s ease; cursor: pointer; border: none; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-outline { background: #fff; color: var(--slate-800); border: 1px solid var(--border); }
    .btn-outline:hover { background: var(--slate-100); }
    .btn-dark { background: var(--slate-900); color: #fff; }
    .btn-dark:hover { background: var(--slate-800); }

    .hero { max-width: 1140px; margin: 3.5rem auto 2.5rem; padding: 0 1.5rem; text-align: center; }
    .hero-badge { display: inline-block; background: #dbeafe; color: #1e40af; font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem; border-radius: 9999px; margin-bottom: 1.25rem; }
    .hero h1 { font-size: 2.5rem; font-weight: 800; color: var(--slate-900); line-height: 1.25; margin-bottom: 1.25rem; }
    .hero p { font-size: 1.15rem; color: var(--slate-600); max-width: 720px; margin: 0 auto 2rem; }
    .hero-cta { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; }

    .grid-section { max-width: 1140px; margin: 3rem auto; padding: 0 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; }
    .card h2 { font-size: 1.25rem; color: var(--slate-900); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
    .card p { font-size: 0.95rem; color: var(--slate-600); margin-bottom: 1.5rem; flex-grow: 1; }
    .card-footer { padding-top: 1rem; border-top: 1px solid var(--slate-100); }

    .feature-list { list-style: none; margin-bottom: 1.5rem; }
    .feature-list li { font-size: 0.9rem; color: var(--slate-600); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
    .feature-list li::before { content: "✔"; color: #16a34a; font-weight: bold; }

    footer { background: #fff; border-top: 1px solid var(--border); padding: 2.5rem 1.5rem; text-align: center; color: var(--slate-600); font-size: 0.85rem; margin-top: 4rem; }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <a href="/" class="logo">
      <span>🎙️ AI面接選考システム</span>
    </a>
    <div class="header-nav">
      <a href="/admin/login.php" class="btn btn-outline">企業ログイン</a>
      <a href="/admin/super_login.php" class="btn btn-dark">統括管理</a>
    </div>
  </div>
</header>

<main>
  <section class="hero">
    <div class="hero-badge">Gemini 3.8 Flash 搭載・リアルタイム音声対話</div>
    <h1>応募者一人ひとりの本質を引き出す<br>次世代AI対話型選考プラットフォーム</h1>
    <p>
      ESの提出から、音声認識・音声合成による自然な双方向面接、リアルタイムな深掘り質問、選考サマリー作成までを一元化。公平かつ迅速な選考プロセスを実現します。
    </p>
    <div class="hero-cta">
      <a href="<?= htmlspecialchars($testEntryUrl) ?>" class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
        🎓 受検体験デモ（学生・応募者向け）
      </a>
      <a href="/admin/login.php" class="btn btn-outline" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
        🏢 企業管理ダッシュボード
      </a>
    </div>
  </section>

  <section class="grid-section">
    <!-- 学生向けカード -->
    <div class="card">
      <div>
        <h2>🎓 学生・受検者の方へ</h2>
        <p>エントリーシートの入力と、ブラウザだけで受検できるAI音声面接のデモフローを体験いただけます。</p>
        <ul class="feature-list">
          <li>事前機器チェック（マイク・カメラ・音声）</li>
          <li>ES内容に応じたパーソナライズ質問</li>
          <li>リアルタイム音声対話と自然な深掘り</li>
        </ul>
      </div>
      <div class="card-footer">
        <a href="<?= htmlspecialchars($testEntryUrl) ?>" class="btn btn-primary" style="width: 100%;">
          テスト受検エントリーを開始
        </a>
      </div>
    </div>

    <!-- 企業採用担当向けカード -->
    <div class="card">
      <div>
        <h2>🏢 企業の採用担当者様</h2>
        <p>応募者一覧の管理、書類選考判定、面接対話レポートの確認、合否判定およびCSV出力を行います。</p>
        <ul class="feature-list">
          <li>自社専用エントリーURLの発行・共有</li>
          <li>面接音声の再生と全文テキスト監査</li>
          <li>AI自動採点（論理性・具体性スコア）の閲覧</li>
        </ul>
      </div>
      <div class="card-footer">
        <a href="/admin/login.php" class="btn btn-outline" style="width: 100%;">
          企業管理画面へログイン
        </a>
      </div>
    </div>

    <!-- スーパー管理者向けカード -->
    <div class="card">
      <div>
        <h2>⚙️ システム統括管理</h2>
        <p>利用企業の追加・発行、全企業データの選考横断監査、システム全体の運用パラメータ設定を行います。</p>
        <ul class="feature-list">
          <li>新規企業アカウント・企業コードの発行</li>
          <li>テナント横断の応募・面接ステータス確認</li>
          <li>プラットフォーム設定の管理</li>
        </ul>
      </div>
      <div class="card-footer">
        <a href="/admin/super_login.php" class="btn btn-dark" style="width: 100%;">
          統括管理者ポータル
        </a>
      </div>
    </div>
  </section>
</main>

<footer>
  <p>&copy; <?= date('Y') ?> AI面接選考システム (aimensetu). All rights reserved.</p>
</footer>

</body>
</html>