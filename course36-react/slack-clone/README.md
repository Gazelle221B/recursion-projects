# Slack Clone

Recursion「React」コースの最終課題として作成した、Slack 風のリアルタイムチャットアプリです。
Google アカウントでログインし、チャンネルを作成して、チャンネルごとにリアルタイムでメッセージをやり取りできます。

## 機能一覧

- **Google 認証ログイン / ログアウト**
  - Firebase Authentication の Google 認証（ポップアップ）でログイン
  - 初回ログイン時にユーザー情報を Firestore の `users` コレクションへ保存
  - サイドバーのプロフィール画像クリックでログアウト
- **チャンネル一覧**
  - Firestore の `channels` コレクションをリアルタイム購読して一覧表示
  - クリックでチャンネルを選択（選択中チャンネルはハイライト表示）
- **チャンネル作成**
  - 「チャンネルを追加する」ボタンからモーダルを表示し、名前を入力して作成
- **メッセージ送受信**
  - 選択中チャンネルのメッセージをリアルタイム購読して表示
  - Enter キーで送信、Shift+Enter で改行（Material UI の TextareaAutosize を使用）
  - 送信者の表示名とプロフィール画像を `users` コレクションから引いて表示
  - 新着メッセージ表示時に自動で最下部へスクロール

## 技術スタック

| 技術 | 用途 |
| --- | --- |
| React + TypeScript（Vite） | UI フレームワーク / ビルドツール |
| Redux Toolkit + react-redux | 状態管理（ユーザーID、選択中チャンネルID） |
| Firebase Authentication | Google 認証 |
| Cloud Firestore | ユーザー / チャンネル / メッセージの保存とリアルタイム購読 |
| Material UI（@mui/material, @mui/icons-material） | アイコン、Avatar、Modal、TextareaAutosize |
| Tailwind CSS | レイアウトとスタイリング |

## セットアップ手順

### 1. Firebase プロジェクトの作成

1. [Firebase コンソール](https://console.firebase.google.com/) にアクセスし、「プロジェクトを追加」から新規プロジェクトを作成します。
2. Google アナリティクスは任意（無効でも可）で進め、プロジェクトを作成します。

### 2. Google 認証の有効化

1. Firebase コンソールの左メニューから「構築」→「Authentication」を開き、「始める」をクリックします。
2. 「Sign-in method」タブで「Google」を選択し、有効化して保存します。

### 3. Firestore の作成

1. 左メニューから「構築」→「Firestore Database」を開き、「データベースの作成」をクリックします。
2. ロケーションは **Tokyo（asia-northeast1）** を選択します。
3. セキュリティルールは「**テストモードで開始**」を選択して作成します。
   - テストモードは開発用です。公開する場合は適切なセキュリティルールに変更してください。

### 4. ウェブアプリの登録と設定値の取得

1. プロジェクトの概要画面で「</>」（ウェブ）アイコンをクリックし、アプリを登録します（ニックネームは任意、Firebase Hosting は不要）。
2. 登録後に表示される `firebaseConfig` の値（apiKey、authDomain、projectId、storageBucket、messagingSenderId、appId）を控えます。
   - 後から確認する場合は「プロジェクトの設定」→「全般」→「マイアプリ」から確認できます。

### 5. .env への記入

プロジェクトルートで `.env.example` を `.env` にコピーし、手順4で取得した設定値を記入します。

```bash
cp .env.example .env
```

```dotenv
VITE_FIREBASE_API_KEY=<手順4の apiKey>
VITE_FIREBASE_AUTH_DOMAIN=<手順4の authDomain>
VITE_FIREBASE_PROJECT_ID=<手順4の projectId>
VITE_FIREBASE_STORAGE_BUCKET=<手順4の storageBucket>
VITE_FIREBASE_MESSAGING_SENDER_ID=<手順4の messagingSenderId>
VITE_FIREBASE_APP_ID=<手順4の appId>
```

`.env` は `.gitignore` に含まれており、リポジトリには公開されません。

### 6. 依存パッケージのインストールと起動

```bash
npm install
npm run dev
```

表示された URL（通常は http://localhost:5173 ）をブラウザで開き、「Google でログイン」からログインするとチャット画面が表示されます。

### その他のコマンド

```bash
npm run build    # 型チェック（tsc）と本番ビルド（vite build）
npm run preview  # ビルド結果のプレビュー
```

## ディレクトリ構成

```
slack-clone/
├── .env.example          # 環境変数のサンプル（キー名のみ）
├── index.html
├── package.json
└── src/
    ├── main.tsx          # エントリポイント（Redux Provider でラップ）
    ├── App.tsx           # ルートコンポーネント（認証状態で画面を切り替え）
    ├── firebase.ts       # Firebase の初期化（設定値は環境変数から読み込み）
    ├── types.ts          # UserType / ChannelType / MessageType の型定義
    ├── index.css         # Tailwind CSS の読み込み
    ├── app/
    │   ├── store.ts      # Redux ストア（configureStore）と型定義
    │   └── hooks.ts      # useAppDispatch / useAppSelector
    ├── features/
    │   ├── user/
    │   │   └── userSlice.ts      # login / logout（ユーザーIDの管理）
    │   └── channel/
    │       └── channelSlice.ts   # selectChannel / clearChannel
    ├── api/
    │   ├── userApi.ts      # getUser / postUser / subscribeUsers
    │   ├── channelApi.ts   # subscribeChannels / addChannel
    │   └── messageApi.ts   # subscribeMessages / postMessage / createMessage
    ├── hooks/
    │   └── useAuthState.ts # onAuthStateChanged を監視するカスタムフック
    └── components/
        ├── Login.tsx         # ログイン画面（Google 認証ボタン）
        ├── Sidebar.tsx       # ナビゲーションとユーザー情報（ログアウト）
        ├── ChatContainer.tsx # ChatList と MessageArea の横並び配置
        ├── ChatList.tsx      # チャンネル一覧と作成モーダルの管理
        ├── ChannelCell.tsx   # チャンネル一覧の1行（選択処理）
        ├── ChannelModal.tsx  # チャンネル作成モーダル
        ├── MessageArea.tsx   # メッセージ表示エリアと入力エリア
        └── MessageTile.tsx   # 個々のメッセージ表示
```

## データモデル（Firestore）

Firestore には3つのコレクションを作成します。日時はすべて Firestore の `Timestamp` / `serverTimestamp` で管理します。

### users

ドキュメントIDは Firebase Authentication の UID です。初回ログイン時に Google アカウントの情報が保存されます。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| displayName | string | 表示名 |
| email | string | メールアドレス |
| photoUrl | string | プロフィール画像の URL |

### channels

ドキュメントIDは自動生成です。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| name | string | チャンネル名 |
| createdAt | Timestamp | 作成日時（serverTimestamp） |

### messages

ドキュメントIDは自動生成です。メッセージ文書には送信者の表示名や画像を持たせず、表示時に `userId` から `users` コレクションを参照します。

| フィールド | 型 | 説明 |
| --- | --- | --- |
| message | string | メッセージ本文 |
| channelId | string | 投稿先チャンネルのドキュメントID |
| userId | string | 送信者の UID |
| createdAt | Timestamp | 送信日時（serverTimestamp） |
