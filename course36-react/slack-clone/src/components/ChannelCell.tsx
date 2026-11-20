import { useAppDispatch, useAppSelector } from "../app/hooks";
import { selectChannel } from "../features/channel/channelSlice";
import type { ChannelType } from "../types";

interface ChannelCellProps {
  channel: ChannelType;
}

/**
 * チャンネル一覧の1行。
 * クリックするとそのチャンネルを選択する。
 */
const ChannelCell = ({ channel }: ChannelCellProps) => {
  const dispatch = useAppDispatch();
  const selectedChannelId = useAppSelector((state) => state.channel.channelId);
  const isSelected = selectedChannelId === channel.id;

  return (
    <button
      type="button"
      onClick={() => dispatch(selectChannel(channel.id))}
      className={`w-full truncate px-4 py-2 text-left transition ${
        isSelected
          ? "bg-[#1164a3] text-white"
          : "text-purple-200 hover:bg-[#350d36]"
      }`}
    >
      # {channel.name}
    </button>
  );
};

export default ChannelCell;
