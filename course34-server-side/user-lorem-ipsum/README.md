# User Lorem Ipsum / Downloadable Lorem Ipsum

Recursion「Server Side Development Project」のプロジェクト。ランダムなユーザーデータを生成し、HTML / Markdown / JSON / テキストで出力・ダウンロードできる PHP アプリケーション。

## 構成

- `Models/User.php` — ユーザークラス。パスワードは `password_hash` でハッシュ化して保持し、`login` は `password_verify` で検証する。`toString()` / `toHTML()` / `toMarkdown()` / `toArray()` で各形式に変換できる
- `Helpers/RandomGenerator.php` — ランダムな User を生成する静的メソッド `user()` / `users(min, max)`。コース版は fakerphp/faker を使用するが、本実装は Composer なしでも動くよう組み込みの単語リストで同等のランダムデータを生成する
- `index.php` — `?min=` / `?max=` クエリパラメータで生成数を変えられる動的ページ
- `generate.php` — 生成数と形式を指定するフォーム
- `download.php` — POST を受け取り、サーバサイド検証（不正な場合は 412 Precondition Failed）のうえ、形式に応じた Content-Type とファイル名でダウンロードさせる

## 実行

```bash
php -S localhost:8000
# http://localhost:8000/          動的ページ
# http://localhost:8000/generate.php  ダウンロード用フォーム
```
