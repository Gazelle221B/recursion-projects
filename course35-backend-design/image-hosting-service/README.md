# Online Image Hosting Service

Recursion「Backend Design & Security Project」の課題（lesson/1246）。Imgur のように、アカウント不要で画像をアップロード・共有・表示できるサービス。

## 機能

- 画像アップロード（JPEG / PNG / GIF、最大10MB）。成功すると一意の画像 URL（`/{media-type}/{slug}`）と削除 URL（`/delete/{delete-slug}`）を発行する
- アップロードは AJAX（fetch + FormData）で行い、結果の URL をその場で表示する
- 画像ページにはビューカウンターを表示し、表示のたびに加算する（実ファイルは `/raw/{slug}` から配信）
- 削除 URL にアクセスするとサーバからファイルとレコードを削除する
- 悪用防止：IP ごとに1時間あたり20件までのアップロードレート制限、形式は finfo で実内容を検証
- データ保持：30日間アクセスのない画像は `cleanup.php`（cron で日次実行）で自動削除する

## 構成

- `public/index.php` — フロントコントローラ（アップロード API、表示、削除）
- `Models/Image.php` — 検証・保存・取得・ビュー登録・削除
- `Database/Database.php` — PDO ラッパー（コースの MySQLWrapper に相当）。接続先は `.env` の DSN で切り替える
- `Database/migrate.php` — images テーブルのマイグレーション
- `cleanup.php` — 保持期限切れ画像の削除（cron 用）
- `storage/` — アップロード画像の保存先（git 管理外にする想定）

## 実行

```bash
cp .env.example .env
php Database/migrate.php
php -S localhost:8000 -t public public/index.php
# http://localhost:8000/
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える。
