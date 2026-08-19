"use client";

import { usePathname, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";

export function NavigationProgress() {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [active, setActive] = useState(false);

  useEffect(() => {
    setActive(false);
  }, [pathname, searchParams]);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
      ) {
        return;
      }

      const target = (event.target as HTMLElement | null)?.closest("a");

      if (!target) {
        return;
      }

      const href = target.getAttribute("href");

      if (!href || href.startsWith("#") || target.target === "_blank" || target.hasAttribute("download")) {
        return;
      }

      try {
        const url = new URL(target.href, window.location.href);

        if (url.origin !== window.location.origin) {
          return;
        }

        if (url.pathname === window.location.pathname && url.search === window.location.search) {
          return;
        }

        setActive(true);
      } catch {
        // Ignore malformed hrefs.
      }
    };

    document.addEventListener("click", onClick);

    return () => document.removeEventListener("click", onClick);
  }, []);

  return (
    <div className={`etic-nav-progress${active ? " is-active" : ""}`} aria-hidden="true">
      <span />
    </div>
  );
}
