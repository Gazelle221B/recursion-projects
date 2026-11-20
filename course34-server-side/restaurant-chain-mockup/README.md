# Restaurant Chain Mockup (+ Extension)

Recursion「Server Side Development Project」の課題。レストランチェーン管理システムのモックアップを PHP の OOP で実装し、ランダム生成した偽データでページを埋める。拡張課題として、フォームで条件を指定してデータを生成・ダウンロードできる。

## 構成

すべてのクラスは `FileConvertible` インターフェース（`toString()` / `toHTML()` / `toMarkdown()` / `toArray()`）を実装し、各クラスにランダム生成用の静的メソッド `RandomGenerator()` を持つ。

- `Models/User.php` — ユーザー（Lorem Ipsum プロジェクトのものを再利用）
- `Models/Employee.php` — User を拡張。職種・給与・開始日・賞のリストを持つ
- `Models/Company.php` — 会社名・設立年・説明・ウェブサイト・電話・業界・CEO・公開取引の有無など
- `Models/RestaurantChain.php` — Company を拡張。チェーンID・場所の配列・料理の種類・ドライブスルーの有無・親会社を持ち、場所の追加と表示ができる
- `Models/RestaurantLocation.php` — 名前・住所・市区町村・州・郵便番号・従業員・営業中かどうか
- `index.php` — ページ読み込み時にランダムな RestaurantChain モック（場所・従業員・会社の詳細）を表示
- `generate.php` / `download.php` — 拡張課題。従業員数、給与範囲、場所の数、郵便番号の範囲、ファイル形式（HTML / JSON / TXT / Markdown）を指定して生成・ダウンロードする。サーバサイド検証付き（不正な場合は 412）

## 実行

```bash
php -S localhost:8000
# http://localhost:8000/             モックアップページ
# http://localhost:8000/generate.php 拡張フォーム
```
