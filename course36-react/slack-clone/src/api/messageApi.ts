import {
  addDoc,
  collection,
  onSnapshot,
  orderBy,
  query,
  serverTimestamp,
  where,
} from "firebase/firestore";
import type { FieldValue, Unsubscribe } from "firebase/firestore";
import { db } from "../firebase";
import type { MessageType } from "../types";

/** Firestore へ保存する新規メッセージのオブジェクト */
export interface MessageInput {
  message: string;
  channelId: string;
  userId: string;
  createdAt: FieldValue;
}

/**
 * 保存用のメッセージオブジェクトを作成する。
 * 日時は Firestore のサーバータイムスタンプで管理する。
 */
export const createMessage = (
  message: string,
  channelId: string,
  userId: string,
): MessageInput => {
  return {
    message,
    channelId,
    userId,
    createdAt: serverTimestamp(),
  };
};

/**
 * 新規メッセージを messages コレクションに追加する。
 */
export const postMessage = async (
  message: string,
  channelId: string,
  userId: string,
): Promise<void> => {
  const newMessage = createMessage(message, channelId, userId);
  await addDoc(collection(db, "messages"), newMessage);
};

/**
 * 特定チャンネルのメッセージを作成日時の昇順でリアルタイム購読する。
 * 戻り値は購読解除関数。
 */
export const subscribeMessages = (
  channelId: string,
  callback: (messages: MessageType[]) => void,
): Unsubscribe => {
  const messagesQuery = query(
    collection(db, "messages"),
    where("channelId", "==", channelId),
    orderBy("createdAt", "asc"),
  );
  return onSnapshot(messagesQuery, (snapshot) => {
    const messages = snapshot.docs.map((messageDoc) => {
      const data = messageDoc.data();
      return {
        id: messageDoc.id,
        message: data.message ?? "",
        channelId: data.channelId ?? "",
        userId: data.userId ?? "",
        createdAt: data.createdAt ?? null,
      } as MessageType;
    });
    callback(messages);
  });
};
