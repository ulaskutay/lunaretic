"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { storeApi } from "./api";
import type { Cart, TrackingEvent } from "./types";

const CART_KEY = "etic_cart_token";
const AUTH_KEY = "etic_auth_token";

type StoreContextValue = {
  cart: Cart | null;
  cartCount: number;
  token: string | null;
  authToken: string | null;
  refreshCart: () => Promise<void>;
  addToCart: (variantId: number, quantity?: number) => Promise<TrackingEvent[]>;
  updateLine: (lineId: number, quantity: number) => Promise<void>;
  removeLine: (lineId: number) => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => Promise<void>;
  setAuthToken: (token: string | null) => void;
  clearCartToken: () => void;
};

const StorefrontContext = createContext<StoreContextValue | null>(null);

function read(key: string): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  return window.localStorage.getItem(key);
}

function write(key: string, value: string | null) {
  if (typeof window === "undefined") {
    return;
  }

  if (value) {
    window.localStorage.setItem(key, value);
  } else {
    window.localStorage.removeItem(key);
  }
}

export function StoreProvider({ children }: { children: React.ReactNode }) {
  const [cart, setCart] = useState<Cart | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [authToken, setAuthTokenState] = useState<string | null>(null);

  const rememberCart = useCallback((next: Cart) => {
    setCart(next);
    if (next.token) {
      setToken(next.token);
      write(CART_KEY, next.token);
    }
  }, []);

  const refreshCart = useCallback(async () => {
    const current = read(CART_KEY);
    const response = await storeApi.cart(current);
    rememberCart(response.data);
  }, [rememberCart]);

  useEffect(() => {
    setToken(read(CART_KEY));
    setAuthTokenState(read(AUTH_KEY));
    refreshCart().catch(() => undefined);
  }, [refreshCart]);

  const addToCart = useCallback(
    async (variantId: number, quantity = 1) => {
      const response = await storeApi.addToCart(variantId, quantity, token);
      rememberCart(response.data);
      return response.events ?? [];
    },
    [rememberCart, token],
  );

  const updateLine = useCallback(
    async (lineId: number, quantity: number) => {
      const response = await storeApi.updateLine(lineId, quantity, token);
      rememberCart(response.data);
    },
    [rememberCart, token],
  );

  const removeLine = useCallback(
    async (lineId: number) => {
      const response = await storeApi.removeLine(lineId, token);
      rememberCart(response.data);
    },
    [rememberCart, token],
  );

  const applyCoupon = useCallback(
    async (code: string) => {
      const response = await storeApi.applyCoupon(code, token);
      rememberCart(response.data);
    },
    [rememberCart, token],
  );

  const removeCoupon = useCallback(async () => {
    const response = await storeApi.removeCoupon(token);
    rememberCart(response.data);
  }, [rememberCart, token]);

  const setAuthToken = useCallback((value: string | null) => {
    setAuthTokenState(value);
    write(AUTH_KEY, value);
  }, []);

  const clearCartToken = useCallback(() => {
    write(CART_KEY, null);
    setToken(null);
    setCart(null);
  }, []);

  const value = useMemo<StoreContextValue>(
    () => ({
      cart,
      cartCount: cart?.lines.reduce((sum, line) => sum + line.quantity, 0) ?? 0,
      token,
      authToken,
      refreshCart,
      addToCart,
      updateLine,
      removeLine,
      applyCoupon,
      removeCoupon,
      setAuthToken,
      clearCartToken,
    }),
    [addToCart, applyCoupon, authToken, cart, clearCartToken, refreshCart, removeCoupon, removeLine, setAuthToken, token, updateLine],
  );

  return <StorefrontContext.Provider value={value}>{children}</StorefrontContext.Provider>;
}

export function useStorefront() {
  const context = useContext(StorefrontContext);

  if (!context) {
    throw new Error("useStorefront must be used within StoreProvider");
  }

  return context;
}
