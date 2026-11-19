import json
import os
import socket
import subprocess
import tempfile
import threading

SERVER_ADDRESS = ("0.0.0.0", 9005)
HEADER_SIZE = 8  # JSONサイズ(2バイト) + メディアタイプサイズ(1バイト) + ペイロードサイズ(5バイト)
BUFFER_SIZE = 65536

processing_ips = set()  # サーバは IP アドレスごとに処理を1つに制限する
processing_lock = threading.Lock()


def recv_exact(conn, size):
    data = b""
    while len(data) < size:
        chunk = conn.recv(size - len(data))
        if not chunk:
            return None
        data += chunk
    return data


def read_message(conn):
    header = recv_exact(conn, HEADER_SIZE)
    if header is None:
        return None, None, None
    json_size = int.from_bytes(header[0:2], "big")
    media_type_size = header[2]
    payload_size = int.from_bytes(header[3:8], "big")
    json_bytes = recv_exact(conn, json_size) if json_size else b"{}"
    media_type_bytes = recv_exact(conn, media_type_size) if media_type_size else b""
    payload = recv_exact(conn, payload_size) if payload_size else b""
    if payload is None:
        return None, None, None
    return json.loads(json_bytes.decode("utf-8")), media_type_bytes.decode("utf-8"), payload


def send_message(conn, json_obj, media_type, payload):
    json_bytes = json.dumps(json_obj).encode("utf-8")
    media_type_bytes = media_type.encode("utf-8")
    header = (len(json_bytes).to_bytes(2, "big")
              + bytes([len(media_type_bytes)])
              + len(payload).to_bytes(5, "big"))
    conn.sendall(header + json_bytes + media_type_bytes + payload)


def send_error(conn, description):
    send_message(conn, {
        "error_code": 500,
        "description": description,
        "solution": "Check the request parameters and the input file, then try again.",
    }, "", b"")


def process_video(input_path, request):
    """FFMPEG で動画処理を行い、(出力ファイルのパス, メディアタイプ) を返す。"""
    operation = request.get("operation")
    workdir = os.path.dirname(input_path)

    if operation == "compress":
        output = os.path.join(workdir, "output.mp4")
        cmd = ["ffmpeg", "-y", "-i", input_path, "-vcodec", "libx264", "-crf", "28", output]
        return cmd, output, "mp4"
    if operation == "resize":
        width = int(request["width"])
        height = int(request["height"])
        output = os.path.join(workdir, "output.mp4")
        cmd = ["ffmpeg", "-y", "-i", input_path, "-vf", f"scale={width}:{height}", output]
        return cmd, output, "mp4"
    if operation == "aspect":
        aspect = str(request["aspect"])  # 例: "16:9"
        w, h = aspect.split(":")
        output = os.path.join(workdir, "output.mp4")
        cmd = ["ffmpeg", "-y", "-i", input_path, "-vf", f"setdar={w}/{h}", "-c:a", "copy", output]
        return cmd, output, "mp4"
    if operation == "mp3":
        output = os.path.join(workdir, "output.mp3")
        cmd = ["ffmpeg", "-y", "-i", input_path, "-vn", "-acodec", "libmp3lame", output]
        return cmd, output, "mp3"
    if operation == "gif":
        start = str(request.get("start", "00:00:00"))
        duration = str(request.get("duration", 3))
        output = os.path.join(workdir, "output.gif")
        cmd = ["ffmpeg", "-y", "-ss", start, "-i", input_path, "-t", duration,
               "-vf", "fps=10,scale=480:-1", output]
        return cmd, output, "gif"
    if operation == "webm":
        start = str(request.get("start", "00:00:00"))
        duration = str(request.get("duration", 3))
        output = os.path.join(workdir, "output.webm")
        cmd = ["ffmpeg", "-y", "-ss", start, "-i", input_path, "-t", duration,
               "-c:v", "libvpx", "-b:v", "1M", "-c:a", "libvorbis", output]
        return cmd, output, "webm"
    raise ValueError(f"Unknown operation: {operation}")


def handle_client(conn, address):
    client_ip = address[0]
    try:
        request, media_type, payload = read_message(conn)
        if request is None:
            return
        with processing_lock:
            if client_ip in processing_ips:
                send_error(conn, "Another process for this IP address is already running.")
                return
            processing_ips.add(client_ip)
        try:
            with tempfile.TemporaryDirectory() as workdir:
                input_path = os.path.join(workdir, f"input.{media_type or 'mp4'}")
                with open(input_path, "wb") as f:
                    f.write(payload)
                cmd, output_path, output_type = process_video(input_path, request)
                result = subprocess.run(cmd, capture_output=True, text=True)
                if result.returncode != 0:
                    send_error(conn, f"FFMPEG failed: {result.stderr.strip().splitlines()[-1] if result.stderr else 'unknown error'}")
                    return
                with open(output_path, "rb") as f:
                    output_data = f.read()
            # 一時ファイルは処理完了後に削除される（TemporaryDirectory）
            send_message(conn, {"status": "success", "operation": request.get("operation")},
                         output_type, output_data)
        finally:
            with processing_lock:
                processing_ips.discard(client_ip)
    except Exception as e:
        try:
            send_error(conn, str(e))
        except OSError:
            pass
    finally:
        conn.close()


def main():
    server_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    server_sock.bind(SERVER_ADDRESS)
    server_sock.listen(5)
    print(f"Video Compressor Service is listening on {SERVER_ADDRESS}")
    try:
        while True:
            conn, address = server_sock.accept()
            threading.Thread(target=handle_client, args=(conn, address), daemon=True).start()
    except KeyboardInterrupt:
        print("\nServer shutting down")
    finally:
        server_sock.close()


if __name__ == "__main__":
    main()
