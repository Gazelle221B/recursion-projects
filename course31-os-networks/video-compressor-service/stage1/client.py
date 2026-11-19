import os
import socket
import sys

SERVER_ADDRESS = ("127.0.0.1", 9004)
HEADER_SIZE = 32
BUFFER_SIZE = 4096


def main():
    if len(sys.argv) != 2:
        print("Usage: python client.py <file.mp4>")
        sys.exit(1)
    file_path = sys.argv[1]
    if not file_path.lower().endswith(".mp4"):
        print("Only mp4 files are supported.")
        sys.exit(1)

    file_size = os.path.getsize(file_path)
    with socket.create_connection(SERVER_ADDRESS) as sock:
        sock.sendall(file_size.to_bytes(HEADER_SIZE, "big"))
        with open(file_path, "rb") as f:
            while True:
                chunk = f.read(BUFFER_SIZE)
                if not chunk:
                    break
                sock.sendall(chunk)
        response = sock.recv(16)
    print(f"Server response: {response.decode('utf-8', errors='replace').strip()}")


if __name__ == "__main__":
    main()
