# PlantUML Server

Recursion「Server Side Development Project」の課題。PlantUML の構文を書くと図（SVG / PNG / ASCII）を返すサービス。

## 機能

- コードエディタで PlantUML を記述し、リアルタイムでプレビューを確認できる
- 図を .svg / .png / .txt 形式でダウンロードできる
- 構文チートシートを掲載
- プラクティスプロンプトジェネレータ（`practice.php`）: `data/prompts.json` の課題一覧をページネーション付きで表示し、各課題には正解例の図が自動生成され、表示／非表示を切り替えて自分の回答と比較できる。JSON に課題を追加すればページと表が動的に増える

## 構成と設計メモ

- `PlantUmlEncoder.php` — PlantUML サーバ用に、テキストを deflate 圧縮して専用の Base64 アルファベットでエンコードする
- `render.php` — 検証のうえ PlantUML サーバへ描画を委譲し、結果を返す。生成ファイルをローカルに保存しないため、ストレージを効率的に使える（課題の非機能要件に対応）
- 描画は公開 PlantUML サーバ（plantuml.com）に委譲している。本番では自前サーバに plantuml.jar をホストし、`render.php` の委譲先を差し替える想定。図の種類（コンポーネント図・デプロイメント図など）の追加は PlantUML 側の対応範囲でそのまま拡張できる

## 実行

```bash
php -S localhost:8000
# http://localhost:8000/             エディタとプレビュー
# http://localhost:8000/practice.php 練習課題
```
