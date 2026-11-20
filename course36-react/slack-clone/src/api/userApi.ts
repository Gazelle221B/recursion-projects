import { collection, doc, getDoc, onSnapshot, setDoc } from "firebase/firestore";
import type { Unsubscribe } from "firebase/firestore";
import { db } from "../firebase";
import type { UserType } from "../types";

/**
 * users コレクションから指定したユーザーのドキュメントを取得する。
 * 存在しない場合は null を返す。
 */
export const getUser = async (uid: string): Promise<UserType | null> => {
  const userRef = doc(db, "users", uid);
  const userSnap = await getDoc(userRef);
  if (!userSnap.exists()) {
    return null;
  }
  const data = userSnap.data();
  return {
    uid: userSnap.id,
    displayName: data.displayName ?? "",
    email: data.email ?? "",
    photoUrl: data.photoUrl ?? "",
  };
};

/**
 * ユーザー情報を users コレクションに保存する。
 * 初回ログイン時は Google アカウントから取得した情報を保存し、
 * 2回目以降は保存済みの情報をそのまま利用する。
 */
export const postUser = async (user: UserType): Promise<UserType> => {
  const existingUser = await getUser(user.uid);
  if (existingUser) {
    return existingUser;
  }
  await setDoc(doc(db, "users", user.uid), {
    displayName: user.displayName,
    email: user.email,
    photoUrl: user.photoUrl,
  });
  return user;
};

/**
 * users コレクション全体を購読し、uid をキーとしたマップで受け取る。
 * メッセージ送信者の表示名・画像を引くために利用する。
 * 戻り値は購読解除関数。
 */
export const subscribeUsers = (
  callback: (users: Record<string, UserType>) => void,
): Unsubscribe => {
  return onSnapshot(collection(db, "users"), (snapshot) => {
    const users: Record<string, UserType> = {};
    snapshot.docs.forEach((userDoc) => {
      const data = userDoc.data();
      users[userDoc.id] = {
        uid: userDoc.id,
        displayName: data.displayName ?? "",
        email: data.email ?? "",
        photoUrl: data.photoUrl ?? "",
      };
    });
    callback(users);
  });
};
