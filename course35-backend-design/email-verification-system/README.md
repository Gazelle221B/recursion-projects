# Email Verification System

Recursion「Backend Design & Security Project」の課題（lesson/1306）。コンピュータ部品アプリで学んだ基盤の上に、署名付き URL による安全なメール検証を実装したシステム。

## 要件と実装

1. **検証ルート** `GET /verify/email`：`id`、`user`（メールアドレスの SHA-256 ハッシュ）、`expiration`、`signature` を受け取る
2. **検証処理**：署名は HMAC-SHA256 で生成し `hash_equals` で照合（`Helpers/SignedUrl.php` がミドルウェア相当の検証を担当）。期限切れ・改ざんは拒否し、サインイン中ユーザーの id とメールハッシュの一致を確認してから `email_verified_at` を更新する
3. **登録時の送信**：登録直後に30分で切れる署名付き検証 URL を生成して送信する（`Mailer::sendVerificationEmail`）
4. **未検証ユーザーの誘導**：未検証でサインインしたユーザーは `/verify/resend` ページへリダイレクトされる。再送ボタンと、メールアドレス変更（変更先へ新しい検証メールを送信）の両方を提供する

その他：パスワードは `password_hash` で保存し、登録時は8文字以上＋大文字・小文字・数字・特殊文字の規則を検証する。ログイン成功時にセッション ID を再生成する。全クエリはプリペアドステートメント。

## メール送信について

本番では PHPMailer（Composer）+ Gmail 等の SMTP リレーで送る想定（コースのメール送信レッスン参照）。この実装では送信内容を `storage/outbox/` にファイルとして書き出すため、メールサーバなしで登録→リンク発行→検証までの全フローを確認できる。`.env` で `MAIL_DELIVERY=mail` にすると PHP の `mail()` 送信も併用する。

## 実行

```bash
cp .env.example .env   # SIGNATURE_SECRET_KEY を必ず変更する
php Database/migrate.php
php -S localhost:8000 -t public public/index.php
# http://localhost:8000/register から登録し、storage/outbox/ のリンクを開く
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える。
