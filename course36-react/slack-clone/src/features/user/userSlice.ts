import { createSlice } from "@reduxjs/toolkit";
import type { PayloadAction } from "@reduxjs/toolkit";

/** ログイン中のユーザーの状態（ユーザーIDのみを保持する） */
interface UserState {
  uid: string | null;
}

const initialState: UserState = {
  uid: null,
};

export const userSlice = createSlice({
  name: "user",
  initialState,
  reducers: {
    /** ログイン: ユーザーIDを保存する */
    login: (state, action: PayloadAction<string>) => {
      state.uid = action.payload;
    },
    /** ログアウト: ユーザーIDをクリアする */
    logout: (state) => {
      state.uid = null;
    },
  },
});

export const { login, logout } = userSlice.actions;
export default userSlice.reducer;
