import os
import socket

SERVER_ADDRESS = ("0.0.0.0", 9004)
HEADER_SIZE = 32  # 最初に送られる32バイトでファイルのバイト数を通知する（最大4GB）
BUFFER_SIZE = 4096
SAVE_DIR = "received"


def recv_exact(conn, size):
    data = b""
    while len(data) < size:
        chunk = conn.recv(size - len(data))
        if not chunk:
            return None
        data += chunk
    return data


def main():
    os.makedirs(SAVE_DIR, exist_ok=True)
    server_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    server_sock.bind(SERVER_ADDRESS)
    server_sock.listen(5)
    print(f"Video upload server is listening on {SERVER_ADDRESS}")

    count = 0
    try:
        while True:
            conn, address = server_sock.accept()
            with conn:
                header = recv_exact(conn, HEADER_SIZE)
                if header is None:
                    continue
                file_size = int.from_bytes(header, "big")
                count += 1
                path = os.path.join(SAVE_DIR, f"upload_{count}.mp4")
                remaining = file_size
                with open(path, "wb") as f:
                    while remaining > 0:
                        chunk = conn.recv(min(BUFFER_SIZE, remaining))
                        if not chunk:
                            break
                        f.write(chunk)
                        remaining -= len(chunk)
                if remaining == 0:
                    print(f"Saved {path} ({file_size} bytes) from {address}")
                    status = "SUCCESS"
                else:
                    # ファイルが完全に到着しなかった場合は保存しない
                    os.remove(path)
                    status = "ERROR"
                # レスポンスはステータス情報を含む16バイトのメッセージ
                conn.sendall(status.encode("utf-8").ljust(16)[:16])
    except KeyboardInterrupt:
        print("\nServer shutting down")
    finally:
        server_sock.close()


if __name__ == "__main__":
    main()
