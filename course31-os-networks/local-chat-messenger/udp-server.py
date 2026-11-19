import os
import socket

from faker import Faker

SERVER_PATH = "/tmp/udp_socket_file"
BUFFER_SIZE = 4096


def main():
    fake = Faker()

    if os.path.exists(SERVER_PATH):
        os.remove(SERVER_PATH)

    server_socket = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
    server_socket.bind(SERVER_PATH)
    print(f"Server is listening on {SERVER_PATH}")

    try:
        while True:
            data, client_address = server_socket.recvfrom(BUFFER_SIZE)
            message = data.decode("utf-8")
            print(f"Received from {client_address}: {message}")

            # faker で偽の応答メッセージを生成して模擬する
            response = fake.sentence()
            server_socket.sendto(response.encode("utf-8"), client_address)
    except KeyboardInterrupt:
        print("\nServer shutting down")
    finally:
        server_socket.close()
        if os.path.exists(SERVER_PATH):
            os.remove(SERVER_PATH)


if __name__ == "__main__":
    main()
