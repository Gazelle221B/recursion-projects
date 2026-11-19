import socket
import sys
import threading

SERVER_IP = "127.0.0.1"
TCP_ADDRESS = (SERVER_IP, 9002)
UDP_ADDRESS = (SERVER_IP, 9003)
HEADER_SIZE = 32
BUFFER_SIZE = 4096


def recv_exact(sock, size):
    data = b""
    while len(data) < size:
        chunk = sock.recv(size - len(data))
        if not chunk:
            return None
        data += chunk
    return data


def request_token(username, room_name, operation):
    """TCP でルームの作成（operation=1）または参加（operation=2）をリクエストし、トークンを受け取る。"""
    room_name_bytes = room_name.encode("utf-8")
    payload = username.encode("utf-8")
    header = bytearray(HEADER_SIZE)
    header[0] = len(room_name_bytes)
    header[1] = operation
    header[2] = 0  # リクエスト
    header[3:32] = len(payload).to_bytes(29, "big")

    token = None
    with socket.create_connection(TCP_ADDRESS) as conn:
        conn.sendall(bytes(header) + room_name_bytes + payload)
        for _ in range(2):
            resp_header = recv_exact(conn, HEADER_SIZE)
            if resp_header is None:
                break
            rn_size = resp_header[0]
            state = resp_header[2]
            payload_size = int.from_bytes(resp_header[3:32], "big")
            body = recv_exact(conn, rn_size + payload_size)
            if body is None:
                break
            payload_bytes = body[rn_size:]
            if state == 1:  # リクエストの応答
                status = payload_bytes.decode("utf-8")
                if status != "200":
                    raise RuntimeError(f"Server returned status {status}")
            elif state == 2:  # リクエストの完了
                token = payload_bytes.decode("utf-8")
    return token


def receive_messages(udp_sock):
    while True:
        try:
            data, _ = udp_sock.recvfrom(BUFFER_SIZE)
        except OSError:
            break
        print(f"\n{data.decode('utf-8', errors='replace')}\n> ", end="", flush=True)


def send_packet(udp_sock, room_name, token, message):
    room_name_bytes = room_name.encode("utf-8")
    token_bytes = token.encode("utf-8")
    packet = bytes([len(room_name_bytes), len(token_bytes)]) + room_name_bytes + token_bytes + message.encode("utf-8")
    udp_sock.sendto(packet, UDP_ADDRESS)


def main():
    username = input("Enter your username: ").strip()
    room_name = input("Enter the chat room name: ").strip()
    mode = input("Create a new room (c) or join an existing room (j)? ").strip().lower()
    operation = 1 if mode == "c" else 2

    try:
        token = request_token(username, room_name, operation)
    except (OSError, RuntimeError) as e:
        print(f"Failed to {'create' if operation == 1 else 'join'} the room: {e}")
        sys.exit(1)
    if token is None:
        print("Failed to receive a token from the server.")
        sys.exit(1)
    print(f"{'Created' if operation == 1 else 'Joined'} room '{room_name}'.")

    udp_sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    udp_sock.bind(("0.0.0.0", 0))
    threading.Thread(target=receive_messages, args=(udp_sock,), daemon=True).start()

    # 参加を知らせ、サーバに自分の UDP アドレスを登録する
    send_packet(udp_sock, room_name, token, f"{username} joined the room.")

    print("Type a message and press Enter (Ctrl+C to quit).")
    try:
        while True:
            message = input("> ")
            send_packet(udp_sock, room_name, token, f"{username}: {message}")
    except (KeyboardInterrupt, EOFError):
        print("\nDisconnected.")
    finally:
        udp_sock.close()


if __name__ == "__main__":
    main()
