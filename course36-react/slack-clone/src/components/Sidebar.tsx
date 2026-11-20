import { useEffect, useState } from "react";
import { signOut } from "firebase/auth";
import Avatar from "@mui/material/Avatar";
import HomeIcon from "@mui/icons-material/Home";
import ChatBubbleIcon from "@mui/icons-material/ChatBubble";
import { auth } from "../firebase";
import { useAppSelector } from "../app/hooks";
import { getUser } from "../api/userApi";
import type { UserType } from "../types";

/**
 * 画面左端の固定幅サイドバー。
 * ナビゲーションアイコンと、ログイン中のユーザー情報を表示する。
 * プロフィール画像をクリックするとログアウトする。
 */
const Sidebar = () => {
  const uid = useAppSelector((state) => state.user.uid);
  const [currentUser, setCurrentUser] = useState<UserType | null>(null);

  useEffect(() => {
    if (!uid) {
      setCurrentUser(null);
      return;
    }
    let isMounted = true;
    getUser(uid).then((user) => {
      if (isMounted) {
        setCurrentUser(user);
      }
    });
    return () => {
      isMounted = false;
    };
  }, [uid]);

  const handleLogout = async () => {
    try {
      await signOut(auth);
    } catch (error) {
      console.error("ログアウトに失敗しました:", error);
    }
  };

  return (
    <div className="flex h-full w-16 flex-col items-center bg-[#3f0e40] py-4">
      {/* ナビゲーション */}
      <nav className="flex flex-col items-center gap-4">
        <button
          type="button"
          className="rounded-md p-2 text-white transition hover:bg-white/20"
          aria-label="ホーム"
        >
          <HomeIcon />
        </button>
        <button
          type="button"
          className="rounded-md p-2 text-white transition hover:bg-white/20"
          aria-label="チャット"
        >
          <ChatBubbleIcon />
        </button>
      </nav>

      {/* ログイン中のユーザー情報（プロフィール画像クリックでログアウト） */}
      <div className="mt-auto flex flex-col items-center gap-1">
        <button
          type="button"
          onClick={handleLogout}
          title={`${currentUser?.displayName ?? ""}（クリックでログアウト）`}
          className="rounded-full transition hover:opacity-80"
        >
          <Avatar
            src={currentUser?.photoUrl || undefined}
            alt={currentUser?.displayName ?? "profile"}
          >
            {currentUser?.displayName ? currentUser.displayName.charAt(0) : ""}
          </Avatar>
        </button>
        <span className="w-14 truncate text-center text-[10px] text-white">
          {currentUser?.displayName ?? ""}
        </span>
      </div>
    </div>
  );
};

export default Sidebar;
