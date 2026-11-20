import { useEffect } from "react";
import { onAuthStateChanged } from "firebase/auth";
import { auth } from "../firebase";
import { useAppDispatch } from "../app/hooks";
import { login, logout } from "../features/user/userSlice";
import { postUser } from "../api/userApi";
import type { UserType } from "../types";

/**
 * Firebase Authentication のログイン状態を監視するカスタムフック。
 * - ログイン時: Firestore にユーザー情報を確保してから Redux に login をディスパッチする
 * - ログアウト時: Redux に logout をディスパッチする
 * - クリーンアップでリスナーを解除する
 */
export const useAuthState = () => {
  const dispatch = useAppDispatch();

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      if (firebaseUser) {
        // 初回ログイン時は Google から取得した情報を users に保存し、
        // 2回目以降は保存済みの情報を利用する
        const userInfo: UserType = {
          uid: firebaseUser.uid,
          displayName: firebaseUser.displayName ?? "",
          email: firebaseUser.email ?? "",
          photoUrl: firebaseUser.photoURL ?? "",
        };
        await postUser(userInfo);
        dispatch(login(firebaseUser.uid));
      } else {
        dispatch(logout());
      }
    });

    return () => {
      unsubscribe();
    };
  }, [dispatch]);
};
