import Avatar from "@mui/material/Avatar";
import type { MessageType, UserType } from "../types";

interface MessageTileProps {
  message: MessageType;
  /** 送信者のユーザー情報（users コレクションから userId で引いたもの） */
  sender?: UserType;
}

/**
 * 個々のメッセージ表示。
 * 本文、送信者の画像と表示名、日時を表示する。
 */
const MessageTile = ({ message, sender }: MessageTileProps) => {
  const formattedDate = message.createdAt
    ? message.createdAt.toDate().toLocaleString("ja-JP")
    : "";

  return (
    <div className="flex gap-3 px-4 py-2 hover:bg-gray-50">
      <Avatar
        src={sender?.photoUrl || undefined}
        alt={sender?.displayName ?? "sender"}
        sx={{ width: 36, height: 36 }}
      >
        {sender?.displayName ? sender.displayName.charAt(0) : ""}
      </Avatar>
      <div className="min-w-0">
        <div className="flex items-baseline gap-2">
          <span className="font-bold text-gray-800">
            {sender?.displayName ?? "Unknown User"}
          </span>
          <span className="text-xs text-gray-500">{formattedDate}</span>
        </div>
        <p className="whitespace-pre-wrap break-words text-gray-800">
          {message.message}
        </p>
      </div>
    </div>
  );
};

export default MessageTile;
