import json
import os
import socket
import sys

SERVER_ADDRESS = ("127.0.0.1", 9005)
HEADER_SIZE = 8
BUFFER_SIZE = 65536


def recv_exact(sock, size):
    data = b""
    while len(data) < size:
        chunk = sock.recv(size - len(data))
        if not chunk:
            return None
        data += chunk
    return data


def send_message(sock, json_obj, media_type, payload):
    json_bytes = json.dumps(json_obj).encode("utf-8")
    media_type_bytes = media_type.encode("utf-8")
    header = (len(json_bytes).to_bytes(2, "big")
              + bytes([len(media_type_bytes)])
              + len(payload).to_bytes(5, "big"))
    sock.sendall(header + json_bytes + media_type_bytes + payload)


def read_message(sock):
    header = recv_exact(sock, HEADER_SIZE)
    if header is None:
        return None, None, None
    json_size = int.from_bytes(header[0:2], "big")
    media_type_size = header[2]
    payload_size = int.from_bytes(header[3:8], "big")
    json_bytes = recv_exact(sock, json_size) if json_size else b"{}"
    media_type_bytes = recv_exact(sock, media_type_size) if media_type_size else b""
    payload = recv_exact(sock, payload_size) if payload_size else b""
    return json.loads(json_bytes.decode("utf-8")), media_type_bytes.decode("utf-8"), payload


def main():
    # 使い方: python client.py <file.mp4> <operation> [key=value ...]
    # operation: compress / resize (width, height) / aspect (aspect=16:9) /
    #            mp3 / gif (start, duration) / webm (start, duration)
    if len(sys.argv) < 3:
        print("Usage: python client.py <file> <operation> [key=value ...]")
        sys.exit(1)
    file_path = sys.argv[1]
    request = {"operation": sys.argv[2]}
    for arg in sys.argv[3:]:
        key, _, value = arg.partition("=")
        request[key] = int(value) if value.isdigit() else value

    media_type = os.path.splitext(file_path)[1].lstrip(".").lower()
    with open(file_path, "rb") as f:
        payload = f.read()

    with socket.create_connection(SERVER_ADDRESS) as sock:
        send_message(sock, request, media_type, payload)
        response, out_type, out_payload = read_message(sock)

    if response is None:
        print("No response from the server.")
        sys.exit(1)
    if "error_code" in response:
        print(f"Error {response['error_code']}: {response['description']}")
        print(f"Solution: {response['solution']}")
        sys.exit(1)
    output_path = f"output.{out_type}"
    with open(output_path, "wb") as f:
        f.write(out_payload)
    print(f"Saved processed file to {output_path} ({len(out_payload)} bytes)")


if __name__ == "__main__":
    main()
