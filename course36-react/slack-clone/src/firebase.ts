import { initializeApp } from "firebase/app";
import { getAuth, GoogleAuthProvider } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

// Firebase の設定値は環境変数（.env）から読み込みます。
// 未設定の場合はプレースホルダーのダミー値にフォールバックします。
// 実際に利用する際は、Firebase コンソールで取得した自分のプロジェクトの
// 設定値を .env に記入してください（.env.example を参照）。
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY ?? "YOUR_API_KEY",
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN ?? "YOUR_AUTH_DOMAIN",
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID ?? "YOUR_PROJECT_ID",
  storageBucket:
    import.meta.env.VITE_FIREBASE_STORAGE_BUCKET ?? "YOUR_STORAGE_BUCKET",
  messagingSenderId:
    import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID ??
    "YOUR_MESSAGING_SENDER_ID",
  appId: import.meta.env.VITE_FIREBASE_APP_ID ?? "YOUR_APP_ID",
};

const app = initializeApp(firebaseConfig);

/** Firebase Authentication */
export const auth = getAuth(app);

/** Cloud Firestore */
export const db = getFirestore(app);

/** Google 認証プロバイダ */
export const provider = new GoogleAuthProvider();
