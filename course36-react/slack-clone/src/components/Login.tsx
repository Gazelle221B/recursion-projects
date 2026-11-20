import { signInWithPopup } from "firebase/auth";
import { auth, provider } from "../firebase";

/**
 * ログイン画面。
 * Google 認証ボタンで Firebase Authentication のポップアップサインインを行う。
 * 未ログイン時は App がこの画面を表示する。
 */
const Login = () => {
  const handleLogin = async () => {
    try {
      await signInWithPopup(auth, provider);
    } catch (error) {
      console.error("ログインに失敗しました:", error);
    }
  };

  return (
    <div className="flex h-screen flex-col items-center justify-center bg-gray-100">
      <h1 className="mb-2 text-4xl font-bold text-gray-800">Slack Clone</h1>
      <p className="mb-8 text-gray-600">
        ログインしてチャットを始めましょう
      </p>
      <button
        type="button"
        onClick={handleLogin}
        className="rounded-md bg-blue-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-blue-700"
      >
        Google でログイン
      </button>
    </div>
  );
};

export default Login;
