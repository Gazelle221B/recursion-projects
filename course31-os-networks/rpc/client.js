const net = require("net");

const SOCKET_PATH = "/tmp/rpc_socket_file";

let nextId = 1;

function call(client, method, params, paramTypes) {
  return new Promise((resolve) => {
    const id = nextId++;
    const request = { method, params, param_types: paramTypes, id };
    const onData = (data) => {
      const response = JSON.parse(data.toString());
      if (response.id === id) {
        client.removeListener("data", onData);
        resolve(response);
      }
    };
    client.on("data", onData);
    client.write(JSON.stringify(request));
  });
}

async function main() {
  const client = net.createConnection(SOCKET_PATH);

  client.on("connect", async () => {
    console.log(await call(client, "floor", [3.7], ["double"]));
    console.log(await call(client, "nroot", [2, 16], ["int", "int"]));
    console.log(await call(client, "reverse", ["hello"], ["string"]));
    console.log(
      await call(client, "validAnagram", ["listen", "silent"], ["string", "string"])
    );
    console.log(
      await call(client, "sort", [["banana", "apple", "cherry"]], ["string[]"])
    );
    client.end();
  });

  client.on("error", (err) => {
    console.error("Connection error:", err.message);
  });
}

main();
