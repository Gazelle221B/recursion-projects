# Local Chat Messenger (UDP)

Recursion「Operating Systems & Networks Project」の課題。UNIX ドメインソケット（SOCK_DGRAM）を使い、クライアントとサーバがメッセージをやり取りするシンプルなアプリケーション。サーバは faker で生成した偽のメッセージで応答を模擬する。

## 実行方法

```bash
pip install faker
python udp-server.py   # サーバを起動
python udp-client.py   # 別のターミナルでクライアントを起動
```

クライアントからメッセージを送ると、サーバが faker の生成した文章を返信する。`quit` で終了。
