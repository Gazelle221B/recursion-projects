# Remote Procedure Call (RPC)

Recursion「Operating Systems & Networks Project」の課題。Python のサーバが提供する関数を、JavaScript（Node.js）のクライアントがソケット経由で呼び出す RPC システム。通信は AF_UNIX ソケット、リクエスト／レスポンスは JSON 形式。

リクエスト: `{"method": "subtract", "params": [42, 23], "param_types": ["int", "int"], "id": 1}`
レスポンス: `{"results": "19", "result_type": "int", "id": 1}`

サーバが提供する関数: `floor(double)`, `nroot(int, int)`, `reverse(string)`, `validAnagram(string, string)`, `sort(string[])`

## 実行方法

```bash
python server.py   # サーバを起動
node client.js     # クライアントが5つの関数を順に呼び出す
```
