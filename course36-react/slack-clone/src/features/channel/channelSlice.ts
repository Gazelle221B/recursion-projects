import { createSlice } from "@reduxjs/toolkit";
import type { PayloadAction } from "@reduxjs/toolkit";

/** 選択中のチャンネルの状態 */
interface ChannelState {
  channelId: string | null;
}

const initialState: ChannelState = {
  channelId: null,
};

export const channelSlice = createSlice({
  name: "channel",
  initialState,
  reducers: {
    /** チャンネルを選択する */
    selectChannel: (state, action: PayloadAction<string>) => {
      state.channelId = action.payload;
    },
    /** チャンネルの選択を解除する */
    clearChannel: (state) => {
      state.channelId = null;
    },
  },
});

export const { selectChannel, clearChannel } = channelSlice.actions;
export default channelSlice.reducer;
