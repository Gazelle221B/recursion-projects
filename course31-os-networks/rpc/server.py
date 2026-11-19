import json
import math
import os
import socket

SOCKET_PATH = "/tmp/rpc_socket_file"
BUFFER_SIZE = 4096


def floor(x):
    return math.floor(x)


def nroot(n, x):
    return x ** (1 / n)


def reverse(s):
    return s[::-1]


def valid_anagram(str1, str2):
    return sorted(str1) == sorted(str2)


def sort_strings(str_arr):
    return sorted(str_arr)


FUNCTIONS = {
    "floor": floor,
    "nroot": nroot,
    "reverse": reverse,
    "validAnagram": valid_anagram,
    "sort": sort_strings,
}

RESULT_TYPES = {
    "floor": "int",
    "nroot": "double",
    "reverse": "string",
    "validAnagram": "bool",
    "sort": "string[]",
}


class SocketServer:
    """ソケットの作成・バインド・接続待ち受けを担当するラッパークラス。"""

    def __init__(self, path):
        self.path = path
        if os.path.exists(path):
            os.remove(path)
        self.server_socket = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
        self.server_socket.bind(path)
        self.server_socket.listen(5)

    def accept(self):
        return self.server_socket.accept()

    def close(self):
        self.server_socket.close()
        if os.path.exists(self.path):
            os.remove(self.path)


class RpcRequestHandler:
    """リクエストの処理とレスポンスの送信を担当するクラス。"""

    def handle(self, connection):
        with connection:
            while True:
                data = connection.recv(BUFFER_SIZE)
                if not data:
                    break
                request = json.loads(data.decode("utf-8"))
                response = self.dispatch(request)
                connection.sendall(json.dumps(response).encode("utf-8"))

    def dispatch(self, request):
        request_id = request.get("id")
        method = request.get("method")
        params = request.get("params", [])
        try:
            func = FUNCTIONS[method]
            result = func(*params)
            return {
                "results": result if isinstance(result, list) else str(result),
                "result_type": RESULT_TYPES[method],
                "id": request_id,
            }
        except Exception as e:
            return {"error": str(e), "id": request_id}


def main():
    server = SocketServer(SOCKET_PATH)
    handler = RpcRequestHandler()
    print(f"RPC server is listening on {SOCKET_PATH}")
    try:
        while True:
            connection, _ = server.accept()
            handler.handle(connection)
    except KeyboardInterrupt:
        print("\nServer shutting down")
    finally:
        server.close()


if __name__ == "__main__":
    main()
