# Social Networking Service

Recursion「Backend Design & Security Project」の課題（lesson/1307）。Twitter 風の軽量 SNS を、既存フレームワークを使わずに独自のマイクロフレームワーク（フロントコントローラ＋ルーティング＋モデル層）で実装したもの。

## 要件と実装

- **アカウントとプロフィール**: 登録・ログイン・ログアウト。パスワードは `password_hash` で保存し、登録時は8文字以上＋大文字・小文字・数字・特殊文字の規則を検証、ログイン成功時にセッション ID を再生成する。プロフィールは名前・年齢・居住地・趣味・自己紹介・写真（画像アップロード）を持ち、本人が編集できる
- **フォロー**: ユーザーのフォロー/フォロー解除、フォロワー数・フォロー数の表示。フォローされると通知が届く
- **投稿**: 255文字まで（`mb_strlen` で検証）。更新は不可、削除は投稿者本人のみ（返信ツリーごと削除）。画像（5 MB まで）・動画（MP4/WebM、20 MB まで）のメディア添付に対応し、実タイプは finfo で判定する
- **いいね**: 投稿へのいいね/取り消し（トグル）。いいねされると投稿者に通知が届く
- **返信スレッド**: 投稿への返信は自己参照（`reply_to_id`）でネスト表示でき、返信先の投稿者へ通知が届く
- **予約投稿**: 投稿時に未来の日時を指定すると `scheduled` 状態で保存され、`publish_scheduled.php` を cron で毎分実行すると公開される
- **DM**: 本文は OpenSSL の AES-256-GCM で暗号化して保存し（鍵は `.env` の `DM_ENCRYPTION_KEY`）、会話を開いたときに復号する。送信すると相手に通知が届く
- **通知**: いいね・返信・フォロー・DM の4種。クリックすると発生源（投稿・プロフィール・会話）へ遷移し、その時点で既読になる。未読数はナビゲーションにバッジ表示する
- **タイムライン**: 「Following」（フォロー中ユーザー＋自分の投稿）と「Trending」（その日のいいね数順）の2タブ。スクロールで20件ずつ自動ロードする（`/api/timeline` の JSON を fetch）
- **シーディング**: `Database/seed.php` が組み込みの単語リストから架空ユーザー・投稿・いいね・返信を生成する（Composer/faker 不要）。既定は120人（インフルエンサー6人）。`php Database/seed.php 3000 50` のように引数で数千人規模にもできる。活動時刻は過去24時間に分散させ、インフルエンサーの投稿には多めにいいねが付くよう生成する。全シードユーザーのパスワードは `Password1!`

## 設計について（要件モデリングの要約）

- **ユースケース**: 訪問者は登録・ログインを行う。ログイン済みユーザーは投稿（即時/予約）・返信・いいね・フォロー・DM・通知確認・プロフィール編集を行う
- **ER 図（文章表現）**: users 1—N posts（posts は reply_to_id で自己参照）、users N—N users（follows 中間テーブル）、users N—N posts（likes 中間テーブル）、users 1—N messages（sender/recipient の2つの FK）、users 1—N notifications
- **クラス構成**: フロントコントローラ（`public/index.php`）がルーティングとビューを担当し、`Models`（User / Post / Message / Notification）がデータアクセス、`Helpers`（Crypto / Media）が暗号化とアップロード、`Database`（PDO ラッパとマイグレーション）が永続化を担当する MVC 風の分離
- **ワイヤーフレーム（文章表現）**: 全ページ共通でヘッダー（サービス名・ナビゲーション）を持ち、ホームは投稿フォーム→タブ→タイムライン、プロフィールはプロフィール情報→投稿一覧、という縦積みレイアウト

## 実行

```bash
cp .env.example .env   # DM_ENCRYPTION_KEY を必ず変更する
php Database/migrate.php
php Database/seed.php  # 任意: デモデータ投入
php -S localhost:8000 -t public public/index.php
# http://localhost:8000/register から登録する
```

予約投稿の公開には cron への登録が必要:

```cron
* * * * * php /path/to/social-networking-service/publish_scheduled.php
```

本番では MySQL を使う想定。ローカルの動作確認用に SQLite の DSN も使える。全クエリはプリペアドステートメント。

## 簡略化した点

- デプロイ（クラウド・自ドメイン・SSL・CI/CD）は対象外で、PHP ビルトインサーバでの動作までを確認している
- シーディングの「毎日ランダムに活動」は、投入時点の過去24時間への分散として近似している（継続実行するスケジューラは未実装）
- スケーラビリティは垂直スケール前提（コース仕様どおり）
