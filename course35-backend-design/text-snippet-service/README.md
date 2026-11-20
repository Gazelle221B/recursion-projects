# Text Snippet Sharing Service

Recursion「Backend Design & Security Project」の課題（lesson/1245）。Pastebin のように、アカウント不要でテキストやコードスニペットを共有できるサービス。

## 機能

- スニペットの投稿（テキストエリア + 言語選択 + 有効期限：10分 / 1時間 / 1日 / 永続）
- 投稿すると一意の URL（`/s/{slug}`）が発行され、シンタックスハイライト付きで閲覧できる（highlight.js）
- 有効期限切れのスニペットはアクセス時に自動削除され、「Expired Snippet」と表示される
- 入力検証：空文字・最大100,000文字・制御文字の混入・未対応言語を拒否し、エラーメッセージを表示する
- すべての DB アクセスはプリペアドステートメント（SQL インジェクション対策）、表示時は htmlspecialchars でエスケープ

## 構成

- `public/index.php` — フロントコントローラ（ルーティング、投稿、閲覧）
- `Models/Snippet.php` — スニペットの生成（hash ベースの一意 slug）、取得、期限判定、削除
- `Database/Database.php` — PDO ラッパー（コースの MySQLWrapper に相当）。接続先は `.env` の DSN で切り替える
- `Database/migrate.php` — snippets テーブルのマイグレーション

## 実行

```bash
cp .env.example .env   # 接続情報を設定
php Database/migrate.php
php -S localhost:8000 -t public public/index.php
# http://localhost:8000/
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える（migrate.php が方言を切り替える）。
