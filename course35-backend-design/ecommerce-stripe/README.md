# E-Commerce Webapp with Stripe Sync

Recursion「Backend Design & Security Project」の課題（lesson/1308）。物理・デジタル両対応の E コマースアプリ。フレームワークは使わず、コース前半で学んだ素の PHP + MVC 構成で実装した（仕様ではフレームワーク使用も可）。

## 商品管理と Stripe 同期

- **商品の作成は Stripe ダッシュボードでのみ**行う。`POST /webhook/stripe` が `product.created` / `product.updated` を受け、バックエンド DB に**下書き**として自動同期する（タイトル・説明・価格・決済リンクは Stripe のデータでプリフィル）
- 管理者は管理ページ（`/admin`）で URL スラッグ・説明・タイトル・カテゴリ・タグ・リリース日・製作者・SEO タグを補完し、**リリースボタンで公開**する
- **削除は両側に同期**する: 管理ページで削除すると Stripe 側の削除も行い（デモモードでは `stripe_sync_queue` にキューとして記録）、Stripe 側で削除されると `product.deleted` Webhook でローカルも削除される
- 在庫はダッシュボード側で設定し Webhook の `metadata.inventory` で同期する。在庫切れの商品は購入できない

Webhook のデモペイロード（デモモードでは署名なしで受け付ける）:

```bash
curl -X POST http://localhost:8001/webhook/stripe -H 'Content-Type: application/json' -d '{
  "type": "product.created",
  "data": {"object": {
    "id": "prod_demo_keyboard", "name": "Demo Mechanical Keyboard",
    "description": "A hot-swappable mechanical keyboard (demo product).",
    "payment_link": "https://buy.stripe.com/demo_keyboard",
    "default_price": {"id": "price_demo_keyboard", "unit_amount": 12800, "currency": "jpy"},
    "metadata": {"type": "physical", "category": "Electronics", "inventory": "10"}
  }}
}'
```

`STRIPE_WEBHOOK_SECRET` を設定すると、`Stripe-Signature` ヘッダーを `hash_hmac` + `hash_equals` で検証する（タイムスタンプ許容誤差5分）。

## 購入とアカウント

- **ゲスト購入**（チェックアウト時のメールで識別）または**アカウント登録**（購入履歴・領収書ページ・プロフィール）
- 決済は Stripe Checkout（line_items）。`checkout.session.completed` Webhook で注文を確定し、在庫を減らす
- **星評価とコメント**（1ユーザー1商品1件、再投稿で更新）、**お気に入り**、**公開ウィッシュリスト**（トークン URL `/wishlist/{token}` で誰でも閲覧可）
- カートはセッションベース。検索とフィルタリング（キーワード、カテゴリ、リリース日の範囲、製作者）に対応

## 注文処理

- 領収書・請求書メールは Stripe が処理する（仕様どおり）
- 物理商品: 支払い完了で注文は「処理中」になり、管理者が**追跡番号**を入力すると「完了」へ変わり、アプリが追跡メールを送る（この実装では `storage/outbox/` に書き出す）
- デジタル商品: 支払い完了で即「完了」。購入者だけが商品ページ/領収書から **zip をダウンロード**できる（zip はその場で生成し、未購入者は 403 で拒否）

## デモモードと環境

`STRIPE_SECRET_KEY` が空の場合はデモモードになり、実際の Stripe へは接続しない。チェックアウトはローカルのデモ決済ページ（`/checkout/demo/...`）で模擬し、「Pay (demo)」で Webhook と同じ注文確定処理が走る。ローカル/ステージング/本番の3環境では、それぞれの `.env` に環境ごとの Stripe テストキーと Webhook を設定する（本番もプロトタイプとしてテストモード前提、という仕様に合わせた）。

## 設計について（要件モデリングの要約）

- **ユースケース**: 訪問者は閲覧・検索・ゲスト購入を行う。顧客はそれに加えて購入履歴・領収書・評価・お気に入り・ウィッシュリスト公開を行う。管理者は商品の補完・公開・削除と追跡番号入力を行う。Stripe は Webhook で商品と決済を同期する外部アクター
- **ER 図（文章表現）**: users 1—N orders 1—N order_items N—1 products（注文明細はタイトル・価格のスナップショットも保持）、users N—N products（favorites 中間テーブル）、users 1—N reviews N—1 products、orders 1—N checkout_sessions、stripe_sync_queue は削除同期のキュー
- **クラス構成**: フロントコントローラ（`public/index.php`）＋ `Models`（User / Product / Order）＋ `Helpers`（StripeClient / Mailer / ZipWriter）＋ `Database`（PDO ラッパとマイグレーション）の MVC 風分離

## 実行

```bash
cp .env.example .env
php Database/migrate.php
php Database/seed.php   # 管理者・デモ顧客・デモ商品3件（下書き）を投入
php -S localhost:8001 -t public public/index.php
# 管理者 admin@example.com / Admin123! でログインし、/admin から商品を補完・公開する
# デモ顧客 customer@example.com / Customer1!
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える。全クエリはプリペアドステートメント。

## 簡略化した点

- デプロイ（クラウド・自ドメイン・SSL・CI/CD）は対象外で、PHP ビルトインサーバでの動作までを確認している
- 実キー使用時の Stripe API 呼び出しは stream context で実装したが、テストキーを持たないため実接続の確認はデモモードの範囲に限る
- 在庫は Webhook の `metadata.inventory` で同期する方式にした（Stripe の在庫管理は商品メタデータ運用が前提）
