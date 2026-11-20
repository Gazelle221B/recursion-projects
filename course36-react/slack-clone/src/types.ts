import type { Timestamp } from "firebase/firestore";

/** users コレクションのドキュメントに対応するユーザー情報 */
export interface UserType {
  uid: string;
  displayName: string;
  email: string;
  photoUrl: string;
}

/** channels コレクションのドキュメントに対応するチャンネル情報 */
export interface ChannelType {
  id: string;
  name: string;
  createdAt: Timestamp | null;
}

/** messages コレクションのドキュメントに対応するメッセージ情報 */
export interface MessageType {
  id: string;
  message: string;
  channelId: string;
  userId: string;
  createdAt: Timestamp | null;
}
