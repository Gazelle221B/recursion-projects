import { useEffect, useRef, useState } from "react";
import type { KeyboardEvent } from "react";
import TextareaAutosize from "@mui/material/TextareaAutosize";
import MessageTile from "./MessageTile";
import { useAppSelector } from "../app/hooks";
import { postMessage, subscribeMessages } from "../api/messageApi";
import { subscribeUsers } from "../api/userApi";
import type { MessageType, UserType } from "../types";

/**
 * メッセージ表示エリアと入力エリア。
 * 選択中チャンネルのメッセージをリアルタイム購読して表示し、
 * TextareaAutosize からの入力を Enter キーで送信する（Shift+Enter は改行）。
 * 送信者の表示名・画像は users コレクションを購読して userId から引く。
 */
const MessageArea = () => {
  const channelId = useAppSelector((state) => state.channel.channelId);
  const uid = useAppSelector((state) => state.user.uid);
  const [messages, setMessages] = useState<MessageType[]>([]);
  const [users, setUsers] = useState<Record<string, UserType>>({});
  const [inputText, setInputText] = useState("");
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // users コレクションを購読し、送信者情報を引けるようにする
  useEffect(() => {
    const unsubscribe = subscribeUsers((fetchedUsers) => {
      setUsers(fetchedUsers);
    });
    return () => {
      unsubscribe();
    };
  }, []);

  // 選択中チャンネルのメッセージをリアルタイム購読する
  useEffect(() => {
    if (!channelId) {
      setMessages([]);
      return;
    }
    const unsubscribe = subscribeMessages(channelId, (fetchedMessages) => {
      setMessages(fetchedMessages);
    });
    return () => {
      unsubscribe();
    };
  }, [channelId]);

  // 新着メッセージが来たら一番下までスクロールする
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const sendMessage = async () => {
    const text = inputText.trim();
    if (!text || !channelId || !uid) {
      return;
    }
    try {
      await postMessage(text, channelId, uid);
      setInputText("");
    } catch (error) {
      console.error("メッセージの送信に失敗しました:", error);
    }
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      void sendMessage();
    }
  };

  if (!channelId) {
    return (
      <div className="flex h-full flex-1 items-center justify-center bg-white">
        <p className="text-gray-500">
          チャンネルを選択するとメッセージが表示されます
        </p>
      </div>
    );
  }

  return (
    <div className="flex h-full flex-1 flex-col bg-white">
      {/* メッセージ表示エリア */}
      <div className="flex-1 overflow-y-auto py-2">
        {messages.map((message) => (
          <MessageTile
            key={message.id}
            message={message}
            sender={users[message.userId]}
          />
        ))}
        <div ref={messagesEndRef} />
      </div>

      {/* 入力エリア */}
      <div className="border-t border-gray-200 p-3">
        <TextareaAutosize
          value={inputText}
          onChange={(event) => setInputText(event.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="メッセージを入力（Enter で送信、Shift+Enter で改行）"
          minRows={1}
          maxRows={5}
          className="w-full resize-none rounded-md border border-gray-300 px-3 py-2 text-gray-800 outline-none focus:border-blue-500"
        />
      </div>
    </div>
  );
};

export default MessageArea;
