import { useState } from "react";
import Box from "@mui/material/Box";
import Modal from "@mui/material/Modal";
import { addChannel } from "../api/channelApi";

interface ChannelModalProps {
  open: boolean;
  onClose: () => void;
}

/**
 * チャンネル作成モーダル。
 * 名前を入力して「作成」を押すと Firestore の channels に登録する。
 * 表示状態は親コンポーネント（ChatList）が管理する。
 */
const ChannelModal = ({ open, onClose }: ChannelModalProps) => {
  const [channelName, setChannelName] = useState("");

  const handleCreate = async () => {
    const name = channelName.trim();
    if (!name) {
      return;
    }
    try {
      await addChannel(name);
      setChannelName("");
      onClose();
    } catch (error) {
      console.error("チャンネルの作成に失敗しました:", error);
    }
  };

  const handleClose = () => {
    setChannelName("");
    onClose();
  };

  return (
    <Modal open={open} onClose={handleClose}>
      <Box className="absolute top-1/2 left-1/2 w-80 -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white p-6 shadow-xl outline-none">
        <h2 className="mb-4 text-lg font-bold text-gray-800">
          チャンネルを作成
        </h2>
        <input
          type="text"
          value={channelName}
          onChange={(event) => setChannelName(event.target.value)}
          placeholder="チャンネル名"
          className="mb-4 w-full rounded border border-gray-300 px-3 py-2 text-gray-800 outline-none focus:border-blue-500"
        />
        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={handleClose}
            className="rounded px-4 py-2 text-gray-600 transition hover:bg-gray-100"
          >
            キャンセル
          </button>
          <button
            type="button"
            onClick={handleCreate}
            className="rounded bg-green-600 px-4 py-2 font-semibold text-white transition hover:bg-green-700"
          >
            作成
          </button>
        </div>
      </Box>
    </Modal>
  );
};

export default ChannelModal;
