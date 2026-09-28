AI対話型面接システム (aimensetu)aimensetu は、エントリーシート（ES）の受付・書類選考から、Webブラウザ上でのリアルタイム対話型AI音声面接、回答の自動文字起こし・定量評価、採用担当者による選考判定・レポート出力までを一貫して自動化・支援するWebシステムです。Google の最新マルチモーダルLLM Gemini 1.5 Flash をコアエンジンに採用し、高速な音声文字起こし（Speech-to-Text）と構造化JSON（Structured Outputs）による動的な深掘り質問・多角的評価を実現しています。🚀 主な機能1. 候補者エントリー・書類受付 (entry.html, api/register.php)柔軟なES受付: 基本情報（氏名・メールアドレス）に加え、自己PR・ガクチカ・志望動機を構造化JSONとして保存。マルチテナント対応: entry.html?company={company_code} 形式で企業ごとの募集フォームを切り替え可能。自動受付メール: 企業別テンプレートによる応募完了控えメールの自動配信。2. 対話型AI音声面接 (index.html, api/start.php, api/answer.php, api/finish.php)音声対話UI: ブラウザの Web Speech API（音声合成・認識）と MediaRecorder（WebM生音声録音）を組み合わせた自然な面接体験。事前環境チェック & 不正防止:カメラ・マイク（音量インジケータ付き）・スピーカーの事前動作テスト。フルスクリーン表示の強制および MediaPipe FaceMesh によるリアルタイム視線・不正監視ログの記録。動的深掘り質問エンジン:候補者の回答内容を分析し、抽象的な場合は発言キーワードを引用して深掘り（follow_up）、十分な場合は次のテーマ（next_theme）へ展開。質問の深さに応じた回答時間制限（30秒 / 60秒 / 120秒）の自動設定。途中中断・復帰（Resume）機能:ネットワーク瞬断や誤リロード時でも、直前の質問から安全に再開（リロード回数の監査ログ記録付き）。3. Gemini 1.5 Flash によるマルチモーダル処理 (api/bootstrap.php)ダイレクト音声文字起こし: 録音された WebM データを Base64 経由で Gemini に直接入力し、高精度な日本語書き起こしを実施。厳格な構造化出力: responseSchema（JSON Schema）を指定することで、パースエラーのない確実な評価スコアと質問生成を実現。100点満点総合評価サマリー: 全ターンの対話履歴を集約し、強み・改善点・論理性・具体性・推薦文を自動算出。4. セキュアな音声配信ゲートウェイ (audio.php)ドキュメントルート外の storage/ 配下に音声を隔離保存。セッション検証・パストラバーサル防御を行い、正規の受検者・管理者のみに音声ストリーミングを提供。5. 管理者ポータル・選考ダッシュボード (admin/)選考ダッシュボード: 候補者の選考ステータス・提出情報の一覧表示。書類選考・合否判定: エントリーシート精査（screening.php）および最終合否判定（pass / fail / hold）と評価コメント入力（judge.php）。面接レポート照会: ターンごとの質疑応答テキスト、録音音声再生、AIスコア詳細の確認（report.php）。CSVエクスポート: 外部ATSや人事DB連携用の一括CSVダウンロード（export_csv.php）。メール・システム設定: 企業ごとの通知テンプレート編集や選考基準の調整。🛠 技術スタックレイヤー技術 / ライブラリ役割バックエンドPHP 8.1+ (8.2+ 推奨)手続き型・関数ベースの軽量設計、厳格な型付け (strict_types=1)データベースMySQL 8.0+ / MariaDB 10.5+UUID v4 主キー、JSONカラム、InnoDB CASCADE制約AI / 音声解析Google Gemini API (gemini-1.5-flash)音声文字起こし (STT)、動的深掘り質問生成、総合評価サマリー音声合成 / 認識Web Speech API, Google Cloud Speech / TTSクライアント側音声読み上げ、補助STT/TTSフロントエンドHTML5, Vanilla JavaScript, CSS3外部フレームワーク非依存のSPA設計、WebRTC (MediaRecorder)画像解析MediaPipe FaceMesh受検中の視線逸脱・不正監視依存関係管理Composervlucas/phpdotenv, ramsey/uuid (未導入環境向けの独自フォールバック内蔵)📂 ディレクトリ構成aimensetu/
├── .env                              # 環境変数設定ファイル (Web公開厳禁)
├── index.html                        # AI面接受検画面 (UI/状態マシン/録音/WebRTC)
├── entry.html                        # 候補者エントリーシート入力フォーム
├── audio.php                         # 音声配信プロキシゲートウェイ (アクセス権限検証)
├── index.php                         # ルートアクセス時のリダイレクタ (/entry.html へ転送)
├── schema.sql                        # データベース定義 (DDL)
├── composer.json                     # 依存ライブラリ定義
├── api/                              # バックエンド REST API
│   ├── bootstrap.php                 # 共通基盤 (DB接続 Singleton, .envパーサー, Gemini API, メール送信)
│   ├── register.php                  # 候補者新規エントリー受付
│   ├── start.php                     # 面接セッション開始 / 復帰 (Resume) 処理
│   ├── answer.php                    # 回答受付・文字起こし・AI評価・深掘り質問生成
│   └── finish.php                    # 面接セッション完了・総合評価サマリー確定
├── admin/                            # 管理者ポータル
│   ├── auth.php                      # セッション認証ガード
│   ├── login.php                     # 企業・管理者ログイン
│   ├── index.php                     # 候補者一覧・選考ダッシュボード
│   ├── screening.php                 # 書類選考 (ES合否)
│   ├── judge.php                     # 最終合否判定・レビューコメント保存
│   ├── report.php                    # 面接詳細レポート照会・音声再生
│   ├── email_settings.php            # 通知メールテンプレート設定
│   ├── settings.php                  # システム・選考パラメータ設定
│   └── export_csv.php                # 選考データ CSV エクスポート
└── storage/                          # 音声・メディア保存領域 (Web直接アクセス遮断)
    ├── uploads/                      # 候補者の回答録音 (WebM)
    └── tts/                          # 音声合成キャッシュ (MP3)
📊 データモデルとステータス遷移データベース構造 (ERD)erDiagram
    candidates ||--o{ interview_sessions : "1:N (CASCADE)"
    interview_sessions ||--o{ interview_turns : "1:N (CASCADE)"

    candidates {
        char(36) id PK "UUIDv4"
        string name "氏名"
        string email "メールアドレス (INDEX)"
        json entry_sheet_data "志望動機・ガクチカ・自己PR"
        enum interview_status "applied / ready / in_progress / completed / es_failed"
        enum final_decision "unreviewed / pass / fail / hold"
        text reviewer_comment "評価者コメント"
        timestamp created_at
    }

    interview_sessions {
        char(36) id PK "UUIDv4"
        char(36) candidate_id FK
        int current_step "現在のターン番号"
        int reload_count "中断・リロード回数"
        timestamp started_at
        timestamp ended_at
        json evaluation_summary "100点満点スコア・強み・弱み・推薦文"
    }

    interview_turns {
        char(36) id PK "UUIDv4"
        char(36) session_id FK
        int turn_number "ターン通番"
        enum question_type "type_a / type_b / type_c"
        text question_text "質問文"
        text answer_text "文字起こし結果"
        string answer_audio_filename "録音ファイル名"
        json turn_score "論理スコア・具体性スコア・メモ"
        timestamp created_at
    }
ステータス遷移面接進捗 (interview_status):
applied (応募完了) ➔ ready (書類通過) / es_failed (書類不通過) ➔ in_progress (面接受検中) ➔ completed (面接終了・AI採点完了)最終合否 (final_decision):
unreviewed (未審査) ➔ pass (合格) / fail (不合格) / hold (保留)⚙️ セットアップ & デプロイ手順1. 動作要件PHP 8.1 以上 (PHP 8.2 / 8.3 推奨)必須拡張モジュール: pdo_mysql, curl, mbstring, json, fileinfoMySQL 8.0+ または MariaDB 10.5+Nginx または ApacheGoogle Gemini API キー（Google AI Studio または Google Cloud Vertex AI）2. インストール# リポジトリのクローン
git clone https://github.com/ipogy/aimensetu.git
cd aimensetu

# Composer パッケージのインストール (任意: 未インストールでも動作可能)
composer install --no-dev --optimize-autoloader

# ストレージディレクトリの作成と権限設定
mkdir -p storage/uploads storage/tts
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
3. データベースの初期化mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS aimensetu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p aimensetu < schema.sql
4. 環境変数の設定 (.env)プロジェクトルートに .env ファイルを作成します。# データベース接続
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=aimensetu
DB_USER=aimensetu_user
DB_PASS=your_db_password

# Google Gemini API 設定 (必須)
GEMINI_API_KEY=AIzaSyYourGeminiApiKeyHere
GEMINI_MODEL=gemini-1.5-flash

# 通知メール配信設定
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="AI面接選考事務局"
Security Note:
.env には機密情報が含まれるため、パーミッションを 600 に設定し、外部Webアクセスを確実に遮断してください。chmod 600 .env
sudo chown www-data:www-data .env
5. Webサーバー設定 (Nginx)/etc/nginx/sites-available/aimensetu.conf:server {
    listen 80;
    server_name mensetu.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name mensetu.example.com;

    root /var/www/aimensetu;
    index index.html index.php;

    # SSL 証明書設定
    ssl_certificate     /etc/letsencrypt/live/mensetu.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mensetu.example.com/privkey.pem;

    # 音声ファイルアップロードサイズ拡張
    client_max_body_size 20M;

    # セキュリティ: .env や .git へのアクセスを禁止
    location ~ /\.(env|git) {
        deny all;
        return 404;
    }

    # storage ディレクトリへの直接ファイルアクセスを遮断 (audio.php 経由で配信)
    location ^~ /storage/ {
        deny all;
        return 403;
    }

    location / {
        try_files $uri $uri/ =404;
    }

    # PHP-FPM 連携
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Gemini API のレスポンスを考慮したタイムアウト延長
        fastcgi_read_timeout 60s;
        fastcgi_send_timeout 60s;
    }
}
🔒 セキュリティ設計アクセス制御 & 音声ゲートウェイ (audio.php):音声ファイルへの直接URLアクセスはNginxで禁止。audio.php がリクエストパラメータの session_id をデータベース照合し、存在確認が取れた場合のみ readfile() で配信。basename() と realpath() を使用し、パストラバーサル（../）攻撃を遮断。推測不能なID体系:オートインクリメントIDを廃止し、すべての外部参照識別子に暗号学的乱数を用いた UUID v4 を採用。SQLインジェクション対策:すべてのクエリで PDO::ATTR_EMULATE_PREPARES => false によるネイティブ・プリペアドステートメントを徹底。情報漏洩防止:api/bootstrap.php にて本番環境向けに ini_set('display_errors', '0') を設定し、スタックトレース等の漏洩を防止。📄 ライセンス本プロジェクトは MIT License のもとで公開されています。