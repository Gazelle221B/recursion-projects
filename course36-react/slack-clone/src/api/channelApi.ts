import {
  addDoc,
  collection,
  onSnapshot,
  orderBy,
  query,
  serverTimestamp,
} from "firebase/firestore";
import type { Unsubscribe } from "firebase/firestore";
import { db } from "../firebase";
import type { ChannelType } from "../types";

/**
 * channels コレクションを作成日時の昇順で購読する。
 * チャンネルが追加・変更されるたびにコールバックが呼ばれる。
 * 戻り値は購読解除関数。
 */
export const subscribeChannels = (
  callback: (channels: ChannelType[]) => void,
): Unsubscribe => {
  const channelsQuery = query(
    collection(db, "channels"),
    orderBy("createdAt", "asc"),
  );
  return onSnapshot(channelsQuery, (snapshot) => {
    const channels = snapshot.docs.map((channelDoc) => {
      const data = channelDoc.data();
      return {
        id: channelDoc.id,
        name: data.name ?? "",
        createdAt: data.createdAt ?? null,
      } as ChannelType;
    });
    callback(channels);
  });
};

/**
 * 新しいチャンネルを channels コレクションに追加登録する。
 */
export const addChannel = async (name: string): Promise<void> => {
  await addDoc(collection(db, "channels"), {
    name,
    createdAt: serverTimestamp(),
  });
};
