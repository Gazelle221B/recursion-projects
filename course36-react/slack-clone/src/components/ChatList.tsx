import { useEffect, useState } from "react";
import ChannelCell from "./ChannelCell";
import ChannelModal from "./ChannelModal";
import { subscribeChannels } from "../api/channelApi";
import type { ChannelType } from "../types";

/**
 * チャンネル一覧エリア（固定幅）。
 * Firestore の channels を購読して一覧表示し、
 * 「チャンネルを追加する」ボタンで作成モーダルを表示する。
 * モーダルの表示状態はこのコンポーネントが管理する。
 */
const ChatList = () => {
  const [channels, setChannels] = useState<ChannelType[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);

  useEffect(() => {
    const unsubscribe = subscribeChannels((fetchedChannels) => {
      setChannels(fetchedChannels);
    });
    return () => {
      unsubscribe();
    };
  }, []);

  return (
    <div className="flex h-full w-60 flex-col bg-[#3f0e40]">
      <div className="border-b border-white/20 px-4 py-3">
        <h2 className="text-lg font-bold text-white">Slack Clone</h2>
      </div>

      <div className="flex-1 overflow-y-auto py-2">
        <p className="px-4 py-1 text-sm font-semibold text-purple-300">
          チャンネル
        </p>
        {channels.map((channel) => (
          <ChannelCell key={channel.id} channel={channel} />
        ))}
      </div>

      <button
        type="button"
        onClick={() => setIsModalOpen(true)}
        className="m-3 rounded border border-white/40 px-3 py-2 text-sm text-white transition hover:bg-white/10"
      >
        + チャンネルを追加する
      </button>

      <ChannelModal open={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </div>
  );
};

export default ChatList;
