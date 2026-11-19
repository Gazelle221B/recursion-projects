import socket
import time

SERVER_ADDRESS = ("0.0.0.0", 9001)
BUFFER_SIZE = 4096
CLIENT_TIMEOUT = 60  # この秒数メッセージがなければリレーシステムから削除


def main():
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind(SERVER_ADDRESS)
    print(f"Chat server is running on {SERVER_ADDRESS}")

    clients = {}  # クライアントのアドレス -> 最後にメッセージを受け取った時刻

    while True:
        data, address = sock.recvfrom(BUFFER_SIZE)
        now = time.time()
        clients[address] = now

        # 一定時間メッセージのないクライアントを削除する
        stale = [a for a, seen in clients.items() if now - seen > CLIENT_TIMEOUT]
        for a in stale:
            del clients[a]

        if len(data) < 1:
            continue
        username_len = data[0]
        username = data[1:1 + username_len].decode("utf-8", errors="replace")
        message = data[1 + username_len:].decode("utf-8", errors="replace")
        print(f"{username} ({address}): {message}")

        # 送信者以外の全クライアントにリレーする
        for client_address in list(clients.keys()):
            if client_address != address:
                sock.sendto(data, client_address)


if __name__ == "__main__":
    main()
