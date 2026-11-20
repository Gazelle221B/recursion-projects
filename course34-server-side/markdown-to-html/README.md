# Markdown to HTML

Recursion「Server Side Development Project」の課題。Markdown を入力すると HTML に変換して表示、またはファイルとしてダウンロードできるウェブアプリケーション。

## 構成

- `index.php` — Monaco エディタ（CDN から読み込み）で Markdown を入力し、表示／ダウンロードを選んで送信するフォーム
- `convert.php` — POST を受け取り、変換して表示するか、`Content-Disposition` で HTML ファイルとしてダウンロードさせる
- `MarkdownConverter.php` — 見出し・太字・斜体・インラインコード・コードブロック・リンク・リスト・段落に対応する小さな変換器。コースでは Parsedown（PHP）や marked（Node.js）などの既存ライブラリの利用が推奨されており、Composer で Parsedown を追加すればそちらに差し替えられる

## 実行

```bash
php -S localhost:8000
# http://localhost:8000/
```
