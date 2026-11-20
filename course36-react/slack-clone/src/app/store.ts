import { configureStore } from "@reduxjs/toolkit";
import userReducer from "../features/user/userSlice";
import channelReducer from "../features/channel/channelSlice";

/** Redux ストア */
export const store = configureStore({
  reducer: {
    user: userReducer,
    channel: channelReducer,
  },
});

/** ルートステートの型 */
export type RootState = ReturnType<typeof store.getState>;

/** ディスパッチの型 */
export type AppDispatch = typeof store.dispatch;
