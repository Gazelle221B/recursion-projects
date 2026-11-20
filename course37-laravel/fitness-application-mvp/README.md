# Fitness Application - MVP

Recursion「Laravel」コース（course/37）の最終課題として実装した、フィットネストラッカーの MVP（最小限の実用製品）です。ユーザーが体重やカロリー摂取を記録し、フィットネス目標の達成をサポートします。認証（Laravel Breeze）付きのフルスタック Laravel アプリケーションで、コースで学んだ fitness-app のスキーマ（Food / FoodType / FoodTag、日誌、カロリー計算）を土台に、課題の機能要件を実装しています。

## 実装した機能要件

1. **日記エントリ管理**: 日々の食事や運動を記録する日記エントリを作成できます。各エントリはユーザーと紐付けられ、日付は UTC で保存されます（フィールド: user_id, date(UTC)）。
2. **食品の CRUD**: 食品項目の作成・検索（名前／食品タイプ／タグ）・更新・削除。総カロリーは protein×4 + carbs×4 + fat×9 で計算し、日誌では食べた量（grams）で按分します。編集・削除は作成者のみ（ポリシーで認可）。
3. **レシピ管理**: 複数の食材で構成されるレシピを作成・管理できます（フィールド: name, creation_date）。レシピと食材の関係は中間テーブル `recipe_food`（recipe_id, food_id）で管理し、詳細画面で食材一覧と合計カロリーを表示します。
4. **運動管理**: 運動の種類（exercise_types。例: 有酸素運動、筋力トレーニング）と運動タグ（exercise_tags。例: 高強度、低負荷。食材タグと同様の多対多設計）を使って、行った運動を時間付きで記録・一覧・削除できます。
5. **日記レシピ項目管理**: 日記エントリにレシピを追加し、そのレシピに含まれる食材を消費したことを記録できます。日誌の総カロリーにはレシピの合計カロリーが加算されます。
6. **日記のファイル保存（プライベート画像）**: 日記にリマインダーとして写真をアップロードできます。画像は非公開ストレージ（`storage/app/private`、local ディスク）に保存され、表示は所有者本人のみがアクセスできる専用コントローラ経由に限定されます（公開ディスクには置きません）。
7. **体重の記録**: 日付ごとの体重の追加と一覧。

### 任意の高度な機能について

課題で「高度な機能（上級ユーザー向け、または挑戦したい方向け）」とされている以下は **未実装** です。

- デプロイ（Docker + NGINX + AWS + CI/CD、または Laravel Forge）
- サブスクリプション管理（Stripe と Laravel Cashier の統合、無料トライアル）
- カスタマーサポート（Reverb WebSockets によるリアルタイムチャット）

## 技術スタック

- PHP 8 / Laravel 11（MVC アーキテクチャ）
- 認証: Laravel Breeze（Blade + Tailwind CSS + Alpine.js）
- データベース: MySQL 8（本番想定。`.env.example` は MySQL 設定）/ SQLite（ローカル確認用）
- フロントエンド: Blade、Tailwind CSS、Vite

## セットアップ手順

### 本番想定（MySQL）

1. MySQL 8 にデータベースとユーザーを作成します（例: データベース `fitness_application`、ユーザー `laravel`）。
2. 依存関係をインストールし、環境ファイルを用意します。

```bash
composer install
cp .env.example .env
php artisan key:generate
```

3. `.env` の `DB_*` を自分の MySQL 設定に合わせます（`.env.example` の初期値は `DB_CONNECTION=mysql` です）。
4. マイグレーションとシーダーを実行し、フロントエンドをビルドしてサーバーを起動します。

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

### ローカル開発（SQLite）

MySQL を用意せずに試す場合は、`.env` で `DB_CONNECTION=sqlite` に変更し、`DB_HOST` などの MySQL 設定をコメントアウトしたうえで `database/database.sqlite` を作成してから同じ手順を実行してください（このリポジトリの検証は SQLite で行いました）。

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### デモユーザー

シーダーでデモユーザーが作成されます。

- メールアドレス: `test@example.com`
- パスワード: `password`

シーダーには他に、食品タイプ（果物、野菜、肉類など）、サンプル食品、食品タグ、運動の種類、運動タグの初期データが含まれます。

## データモデル（ER の文章説明）

- **users**（Breeze 標準）: すべての記録の所有者。食品・日誌・レシピ・運動・体重と 1 対多でつながります。
- **food_types**: 食品の種類。`name` が文字列主キーです。
- **foods**: 食品。`name` はユニーク。protein / carbs / fat は基準量 `grams` あたりの栄養素で、`food_type_name` が food_types への外部キー、`user_id`（nullable）が作成者を指します（null はシステム登録）。
- **food_tags** と中間テーブル **food_food_tag**: 食品とタグの多対多。
- **weight_entries**: ユーザーの体重記録（user_id、weight、recorded_on）。
- **diary_entries**: 日誌（user_id、date は UTC 保存、image_path はプライベート画像のパス）。
- **diary_food_items**: 日誌に食べた食品を記録（diary_entry_id、food_id、grams）。
- **recipes**: レシピ（user_id、name、creation_date）。
- **recipe_food**: レシピと食材の中間テーブル（recipe_id、food_id）。
- **exercise_types**: 運動の種類（name）。
- **exercise_tags** と中間テーブル **exercise_exercise_tag**: 運動タグと運動記録の多対多。
- **exercises**: 運動の記録（user_id、exercise_type_id、duration_minutes、performed_at）。
- **diary_recipe_items**: 日誌にレシピを追加した記録（diary_entry_id、recipe_id）。

## ディレクトリ構成

- `app/Models/` — Eloquent モデル（Food、FoodType、FoodTag、DiaryEntry、DiaryFoodItem、Recipe、DiaryRecipeItem、Exercise、ExerciseType、ExerciseTag、WeightEntry）
- `app/Http/Controllers/Dashboard/` — ダッシュボード配下のコントローラ（Food、DiaryEntry、DiaryFoodItem、DiaryRecipeItem、Recipe、Exercise、WeightEntry）
- `app/Http/Requests/` — フォームリクエスト（食品・レシピのバリデーションと認可）
- `app/Policies/` — ポリシー（FoodPolicy: 閲覧・作成は全ユーザー、編集・削除は作成者のみ。RecipePolicy: 所有者のみ）
- `database/migrations/` — スキーマ定義（バージョン管理されたマイグレーション）
- `database/seeders/` — 初期データ（食品タイプ、タグ、サンプル食品、運動マスタ、デモユーザー）
- `resources/views/dashboard/` — 各機能の Blade ビュー
- `routes/web.php` — Web ルート（`/dashboard` 配下に認証必須で配置）
- `tests/Feature/FitnessFlowTest.php` — 主要フローと認可・プライベート画像の検証テスト

## セキュリティ

- **入力検証**: フォームリクエストとコントローラのバリデーションで、すべてのユーザー入力をサーバー側で検証します（本番環境では HTTPS の利用を前提とします）。
- **認証・認可**: ダッシュボード配下は全てログイン必須で、日誌・レシピ・運動・体重は所有者本人のデータのみ操作できます。食品の編集・削除はポリシーで作成者に限定しています。
- **プライベート画像**: 日記の画像は公開ディスクではなく `storage/app/private` に保存し、所有者チェックを行う専用ルートからのみ配信します。

## 検証

- `php artisan migrate:fresh --seed`（SQLite）が成功すること
- `php artisan route:list` で主要ルートが出ること
- `php artisan test` — 全テスト通過（FitnessFlowTest で食品作成→日誌作成→食品項目追加→レシピ作成・日誌追加→運動記録→体重記録の一連の流れ、食品の認可、プライベート画像のアクセス制御を確認）
- 開発サーバーで `/`、`/login`、`/register` が 200 を返し、未ログインの `/dashboard` がログインへリダイレクトされること
