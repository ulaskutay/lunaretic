"use client";

import { useEffect } from "react";

export function PreventZoom() {
  useEffect(() => {
    const blockGesture = (event: Event) => {
      event.preventDefault();
    };

    const blockPinch = (event: TouchEvent) => {
      if (event.touches.length > 1) {
        event.preventDefault();
      }
    };

    let lastTouchEnd = 0;
    const blockDoubleTap = (event: TouchEvent) => {
      const now = Date.now();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    };

    document.addEventListener("gesturestart", blockGesture, { passive: false });
    document.addEventListener("gesturechange", blockGesture, { passive: false });
    document.addEventListener("gestureend", blockGesture, { passive: false });
    document.addEventListener("touchmove", blockPinch, { passive: false });
    document.addEventListener("touchend", blockDoubleTap, { passive: false });

    return () => {
      document.removeEventListener("gesturestart", blockGesture);
      document.removeEventListener("gesturechange", blockGesture);
      document.removeEventListener("gestureend", blockGesture);
      document.removeEventListener("touchmove", blockPinch);
      document.removeEventListener("touchend", blockDoubleTap);
    };
  }, []);

  return null;
}
