import Sidebar from "./components/Sidebar";
import ChatContainer from "./components/ChatContainer";
import Login from "./components/Login";
import { useAppSelector } from "./app/hooks";
import { useAuthState } from "./hooks/useAuthState";

/**
 * アプリのルートコンポーネント。
 * 認証状態を監視し、未ログイン時はログイン画面を表示する。
 * ログイン時は Sidebar（固定幅）と ChatContainer（残り幅）を
 * flexbox で横並びに配置する。
 */
function App() {
  useAuthState();
  const uid = useAppSelector((state) => state.user.uid);

  if (!uid) {
    return <Login />;
  }

  return (
    <div className="flex h-screen w-full">
      <Sidebar />
      <ChatContainer />
    </div>
  );
}

export default App;
