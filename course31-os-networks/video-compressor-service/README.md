# Video Compressor Service

Recursion「Operating Systems & Networks Project」の課題。動画ファイルをサーバにアップロードし、サーバ側のリソースと FFMPEG を使って動画処理を行うサービス。要件: ffmpeg がインストールされていること。

## ステージ1（stage1/）

TCP で mp4 ファイルをアップロードする。クライアントは最初に32バイトでファイルのバイト数を通知し、ファイル本体を送る。サーバは受け取ったデータを mp4 として保存し、16バイトのステータスメッセージで応答する。

```bash
python stage1/server.py
python stage1/client.py movie.mp4
```

## ステージ2（stage2/）

カスタムプロトコル（8バイトヘッダ: JSONサイズ 2バイト、メディアタイプサイズ 1バイト、ペイロードサイズ 5バイト）で通信し、FFMPEG で動画処理を行って結果を返す。エラー時はエラーコード・説明・解決策を含む JSON が返る。サーバは IP アドレスごとに処理を1つに制限し、一時ファイルは処理後に削除される。

処理の種類: `compress`（圧縮）、`resize`（解像度変更、width/height 指定）、`aspect`（アスペクト比変更、aspect=16:9）、`mp3`（音声変換）、`gif`（時間範囲で GIF 作成、start/duration 指定）、`webm`（時間範囲で WEBM 作成）

```bash
python stage2/server.py
python stage2/client.py movie.mp4 compress
python stage2/client.py movie.mp4 mp3
python stage2/client.py movie.mp4 gif start=00:00:01 duration=3
```
