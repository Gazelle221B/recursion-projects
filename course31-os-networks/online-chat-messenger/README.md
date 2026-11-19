# Online Chat Messenger

Recursion「Operating Systems & Networks Project」の課題。クライアントサーバ型のオンラインチャットメッセンジャー。

## ステージ1（stage1/）

UDP ネットワークソケットを使った基本的なチャット。サーバは接続中の全クライアントのアドレスを保持し、届いたメッセージを他の全クライアントにリレーする。パケット形式: 先頭1バイトがユーザー名長、続いてユーザー名、その後がメッセージ本文（UTF-8、最大4096バイト）。

```bash
python stage1/server.py
python stage1/client.py  # 複数ターミナルで起動
```

## ステージ2（stage2/）

チャットルーム機能を追加。ルームの作成・参加は TCP（32バイトヘッダ: RoomNameSize 1バイト、Operation 1バイト、State 1バイト、OperationPayloadSize 29バイト）で行い、サーバが発行するトークンでクライアントを識別する。ルーム内の会話は UDP（先頭2バイトがルーム名長とトークン長）で行う。ホストが退出するとルームは自動的に閉じられる。

```bash
python stage2/server.py
python stage2/client.py  # 起動時にルームの作成/参加を選ぶ
```
