# Resume Website

Recursion「Server Deployment Project」の課題。自分のレジュメとポートフォリオを紹介する静的ウェブサイト。

## 構成

- `index.html` — ホーム（自己紹介と各ページの案内）
- `portfolio.html` — プロジェクト一覧（`data/projects.json` を JavaScript で読み込んで描画。JSON を編集するだけで追加・削除できる）
- `resume.html` — レジュメ（スキル・経験・学歴）
- `assets/resume.pdf` — ダウンロード用履歴書 PDF
- 全ページ共通レイアウト（ヘッダーのナビゲーションとフッター）

## デプロイ（手順）

1. 新規 EC2 インスタンス（Ubuntu）を作成し、SSH で接続する
2. NGINX をインストールして起動する
3. サブドメイン（例: resume.example.org）向けのサーバブロックを設定し、このディレクトリの内容をドキュメントルートに配置する
4. DNS の A レコードでサブドメインをインスタンスの IP に向ける
5. Certbot（Let's Encrypt）で証明書を取得し、HTTPS 化する
6. 以後の更新は rsync 等の単一コマンドでローカルから同期する
