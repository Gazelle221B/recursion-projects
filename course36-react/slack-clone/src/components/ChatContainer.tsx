import ChatList from "./ChatList";
import MessageArea from "./MessageArea";

/**
 * チャット画面全体のコンテナ。
 * チャンネル一覧（固定幅）とメッセージエリア（残り幅）を横並びに配置する。
 */
const ChatContainer = () => {
  return (
    <div className="flex h-full flex-1">
      <ChatList />
      <MessageArea />
    </div>
  );
};

export default ChatContainer;
