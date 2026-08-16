"use client";

import { useEffect, useState } from "react";
import type { ThemeCountdown } from "@/lib/types";

type Remaining = {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
};

const emptyRemaining: Remaining = { days: 0, hours: 0, minutes: 0, seconds: 0 };

function calculateRemaining(endsAt: string): Remaining {
  const target = Date.parse(endsAt);

  if (Number.isNaN(target)) {
    return emptyRemaining;
  }

  const totalSeconds = Math.floor(Math.max(0, target - Date.now()) / 1000);

  return {
    days: Math.floor(totalSeconds / 86400),
    hours: Math.floor((totalSeconds % 86400) / 3600),
    minutes: Math.floor((totalSeconds % 3600) / 60),
    seconds: totalSeconds % 60,
  };
}

export function CountdownBanner({ countdown }: { countdown?: ThemeCountdown }) {
  const [remaining, setRemaining] = useState<Remaining>(emptyRemaining);
  const endsAt = countdown?.ends_at;

  useEffect(() => {
    if (!endsAt) {
      return;
    }

    const update = () => setRemaining(calculateRemaining(endsAt));

    update();
    const interval = window.setInterval(update, 1000);

    return () => window.clearInterval(interval);
  }, [endsAt]);

  if (!endsAt || countdown?.enabled === false) {
    return null;
  }

  const units = [
    ["Gün", remaining.days],
    ["Saat", remaining.hours],
    ["Dakika", remaining.minutes],
    ["Saniye", remaining.seconds],
  ] as const;

  return (
    <section className="etic-countdown" aria-labelledby="atelier-countdown-title">
      <div className="etic-countdown__copy">
        <h2 id="atelier-countdown-title">{countdown?.title || "Sezon indirimi"}</h2>
        <p>{countdown?.description || "Seçili ürünlerde sınırlı süreli fırsatları keşfedin."}</p>
      </div>
      <div className="etic-countdown__timer" role="timer" aria-label="Kampanya için kalan süre">
        {units.map(([label, value]) => (
          <div key={label} className="etic-countdown__unit">
            <strong>{String(value).padStart(2, "0")}</strong>
            <span>{label}</span>
          </div>
        ))}
      </div>
    </section>
  );
}
