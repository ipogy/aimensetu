erDiagram
    candidates ||--o{ interview_sessions : "1:N (ON DELETE CASCADE)"
    interview_sessions ||--o{ interview_turns : "1:N (ON DELETE CASCADE)"

    candidates {
        char36 id PK "UUIDv4"
        string name "氏名"
        string email "メールアドレス (INDEX)"
        json entry_sheet_data "ES提出内容 (自己PR, ガクチカ, 志望動機)"
        enum interview_status "applied / ready / in_progress / completed / es_failed"
        enum final_decision "unreviewed / pass / fail / hold"
        text reviewer_comment "評価者コメント"
        timestamp created_at
    }

    interview_sessions {
        char36 id PK "UUIDv4"
        char36 candidate_id FK
        int current_step "現在のターン番号"
        int reload_count "中断・リロード回数"
        timestamp started_at
        timestamp ended_at
        json evaluation_summary "総合評価スコア・長所・改善点"
    }

    interview_turns {
        char36 id PK "UUIDv4"
        char36 session_id FK
        int turn_number "ターン通番"
        enum question_type "main / follow_up"
        text question_text "質問内容テキスト"
        string question_audio_filename "質問音声ファイル名"
        text answer_text "回答文字起こしテキスト"
        string answer_audio_filename "回答録音ファイル名"
        json turn_score "ターン別AI評価スコア"
        timestamp created_at
    }