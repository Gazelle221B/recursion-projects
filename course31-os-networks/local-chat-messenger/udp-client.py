import os
import socket

SERVER_PATH = "/tmp/udp_socket_file"
CLIENT_PATH = "/tmp/udp_socket_file_client"
BUFFER_SIZE = 4096


def main():
    if os.path.exists(CLIENT_PATH):
        os.remove(CLIENT_PATH)

    client_socket = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
    # データグラムではサーバが返信先を知るためにクライアント側もバインドする
    client_socket.bind(CLIENT_PATH)

    try:
        while True:
            message = input("Enter a message (type 'quit' to exit): ")
            if message.lower() == "quit":
                break
            client_socket.sendto(message.encode("utf-8"), SERVER_PATH)
            data, _ = client_socket.recvfrom(BUFFER_SIZE)
            print(f"Response from server: {data.decode('utf-8')}")
    finally:
        client_socket.close()
        if os.path.exists(CLIENT_PATH):
            os.remove(CLIENT_PATH)


if __name__ == "__main__":
    main()
