import { useDispatch, useSelector } from "react-redux";
import type { TypedUseSelectorHook } from "react-redux";
import type { RootState, AppDispatch } from "./store";

/** 型付きの useDispatch */
export const useAppDispatch = () => useDispatch<AppDispatch>();

/** 型付きの useSelector */
export const useAppSelector: TypedUseSelectorHook<RootState> = useSelector;
