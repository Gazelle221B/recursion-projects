import secrets
import socket
import threading
import time

TCP_ADDRESS = ("0.0.0.0", 9002)
UDP_ADDRESS = ("0.0.0.0", 9003)
HEADER_SIZE = 32
BUFFER_SIZE = 4096
CLIENT_TIMEOUT = 30  # この秒数 UDP パケットがなければリレーシステムから削除

rooms = {}  # ルーム名 -> {"host_token": str, "members": {token: {...}}}
rooms_lock = threading.Lock()


def recv_exact(conn, size):
    data = b""
    while len(data) < size:
        chunk = conn.recv(size - len(data))
        if not chunk:
            return None
        data += chunk
    return data


def send_tcp_message(conn, room_name, operation, state, payload_bytes):
    room_name_bytes = room_name.encode("utf-8")
    header = bytearray(HEADER_SIZE)
    header[0] = len(room_name_bytes)
    header[1] = operation
    header[2] = state
    header[3:32] = len(payload_bytes).to_bytes(29, "big")
    conn.sendall(bytes(header) + room_name_bytes + payload_bytes)


def handle_tcp_client(conn, address):
    try:
        header = recv_exact(conn, HEADER_SIZE)
        if header is None:
            return
        room_name_size = header[0]
        operation = header[1]
        payload_size = int.from_bytes(header[3:32], "big")
        body = recv_exact(conn, room_name_size + payload_size)
        if body is None:
            return
        room_name = body[:room_name_size].decode("utf-8")
        username = body[room_name_size:].decode("utf-8")
        client_ip = address[0]

        token = None
        with rooms_lock:
            if operation == 1:  # チャットルームの作成
                if room_name in rooms:
                    send_tcp_message(conn, room_name, operation, 1, b"400")
                    return
                token = secrets.token_hex(16)
                rooms[room_name] = {
                    "host_token": token,
                    "members": {token: {"username": username, "ip": client_ip,
                                        "udp_addr": None, "last_seen": time.time()}},
                }
            elif operation == 2:  # チャットルームへの参加
                if room_name not in rooms:
                    send_tcp_message(conn, room_name, operation, 1, b"404")
                    return
                token = secrets.token_hex(16)
                rooms[room_name]["members"][token] = {
                    "username": username, "ip": client_ip,
                    "udp_addr": None, "last_seen": time.time(),
                }
            else:
                send_tcp_message(conn, room_name, operation, 1, b"400")
                return

        # リクエストの応答（1）: ステータスコード、リクエストの完了（2）: トークン
        send_tcp_message(conn, room_name, operation, 1, b"200")
        send_tcp_message(conn, room_name, operation, 2, token.encode("utf-8"))
    finally:
        conn.close()


def udp_loop(udp_sock):
    while True:
        data, address = udp_sock.recvfrom(BUFFER_SIZE)
        if len(data) < 2:
            continue
        room_name_size = data[0]
        token_size = data[1]
        room_name = data[2:2 + room_name_size].decode("utf-8", errors="replace")
        token = data[2 + room_name_size:2 + room_name_size + token_size].decode("utf-8", errors="replace")
        message = data[2 + room_name_size + token_size:]

        with rooms_lock:
            room = rooms.get(room_name)
            if room is None:
                continue
            member = room["members"].get(token)
            # トークンの所有者（IP アドレスが一致）からのパケットだけを受け付ける
            if member is None or member["ip"] != address[0]:
                continue
            member["udp_addr"] = address
            member["last_seen"] = time.time()
            targets = [m["udp_addr"] for t, m in room["members"].items()
                       if t != token and m["udp_addr"] is not None]

        for target in targets:
            udp_sock.sendto(message, target)


def cleanup_loop(udp_sock):
    while True:
        time.sleep(5)
        now = time.time()
        with rooms_lock:
            for room_name in list(rooms.keys()):
                room = rooms[room_name]
                for token in list(room["members"].keys()):
                    member = room["members"][token]
                    if now - member["last_seen"] <= CLIENT_TIMEOUT:
                        continue
                    addr = member["udp_addr"]
                    del room["members"][token]
                    if addr is not None:
                        try:
                            udp_sock.sendto(b"[server] You were disconnected due to inactivity.", addr)
                        except OSError:
                            pass
                    if token == room["host_token"]:
                        # ホストが退出するとチャットルームは自動的に閉じられる
                        for m in room["members"].values():
                            if m["udp_addr"] is not None:
                                try:
                                    udp_sock.sendto(b"[server] The host left. The room is closed.", m["udp_addr"])
                                except OSError:
                                    pass
                        del rooms[room_name]
                        break


def main():
    udp_sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    udp_sock.bind(UDP_ADDRESS)

    tcp_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    tcp_sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    tcp_sock.bind(TCP_ADDRESS)
    tcp_sock.listen(5)

    threading.Thread(target=udp_loop, args=(udp_sock,), daemon=True).start()
    threading.Thread(target=cleanup_loop, args=(udp_sock,), daemon=True).start()

    print(f"Server is running. TCP: {TCP_ADDRESS}, UDP: {UDP_ADDRESS}")
    try:
        while True:
            conn, address = tcp_sock.accept()
            threading.Thread(target=handle_tcp_client, args=(conn, address), daemon=True).start()
    except KeyboardInterrupt:
        print("\nServer shutting down")


if __name__ == "__main__":
    main()
