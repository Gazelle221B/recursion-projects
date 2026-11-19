import socket
import sys
import threading

SERVER_ADDRESS = ("127.0.0.1", 9001)
BUFFER_SIZE = 4096


def receive_messages(sock):
    while True:
        try:
            data, _ = sock.recvfrom(BUFFER_SIZE)
        except OSError:
            break
        if len(data) < 1:
            continue
        username_len = data[0]
        username = data[1:1 + username_len].decode("utf-8", errors="replace")
        message = data[1 + username_len:].decode("utf-8", errors="replace")
        print(f"\n{username}: {message}\n> ", end="", flush=True)


def main():
    username = input("Enter your username: ").strip()
    username_bytes = username.encode("utf-8")
    if len(username_bytes) > 255:
        print("Username is too long (max 255 bytes).")
        sys.exit(1)

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind(("0.0.0.0", 0))  # サーバからの応答を受け取るために自分のアドレスを紐付ける

    thread = threading.Thread(target=receive_messages, args=(sock,), daemon=True)
    thread.start()

    # 接続を知らせるパケットを送り、サーバに自分のアドレスを登録する
    join_packet = bytes([len(username_bytes)]) + username_bytes + f"{username} joined the chat.".encode("utf-8")
    sock.sendto(join_packet, SERVER_ADDRESS)

    print("Type a message and press Enter (Ctrl+C to quit).")
    try:
        while True:
            message = input("> ")
            packet = bytes([len(username_bytes)]) + username_bytes + message.encode("utf-8")
            sock.sendto(packet, SERVER_ADDRESS)
    except (KeyboardInterrupt, EOFError):
        print("\nDisconnected.")
    finally:
        sock.close()


if __name__ == "__main__":
    main()
