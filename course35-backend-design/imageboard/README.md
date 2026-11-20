# Imageboard Webapp

Recursion「Backend Design & Security Project」の課題（lesson/1256）。匿名で画像とテキストを投稿できる、スレッド式のイメージボード。

## 機能

- スレッド作成（画像必須、件名は任意）と、スレッドへの返信（画像は任意）。すべての投稿は匿名
- Post は自己参照モデル：`reply_to_id` が null の投稿がメインスレッド、値がある投稿がそのスレッドへの返信
- 一覧では各スレッドと最新5件の返信を表示し、スレッドページでは全返信を表示する
- 画像は post_id と作成時刻からハッシュ化したファイル名で保存し、150x150 のサムネイルを生成する（ImageMagick の `convert` を優先し、なければ GD にフォールバック）。対応形式は JPEG / PNG / GIF のみ
- 検証：本文の空・長さ（4000文字）、件名の長さ、画像サイズ（5MB）と形式にエラーメッセージを表示する
- シーダー（`Database/seed.php`）がダミーのスレッドと返信を投入する（プレースホルダ画像は GD で生成）

## 構成（DAO パターン）

- `Models/Post.php` — Post エンティティ（POCO）
- `Database/PostDAO.php` — DAO インターフェース（create / getById / update / delete / createOrUpdate / getAllThreads / getReplies）
- `Database/PostDAOImpl.php` — PDO による実装。すべてのクエリはプリペアドステートメント
- `Database/Database.php` — 接続マネージャ（コースの DatabaseManager に相当）。接続先は `.env` の DSN で切り替える
- `Database/migrate.php` / `Database/seed.php`
- `Helpers/ImageUploader.php` — 検証・保存・サムネイル生成
- `public/index.php` — フロントコントローラ。`storage/` はアップロード画像用で git 管理外にする想定（ローカル確認用にルート経由で配信している）

## 実行

```bash
cp .env.example .env
php Database/migrate.php
php Database/seed.php        # 任意：ダミーデータ
php -S localhost:8000 -t public public/index.php
# http://localhost:8000/
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える。
