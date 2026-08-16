"use client";

/* eslint-disable @next/next/next-script-for-ga */

import { useEffect } from "react";
import type { Bootstrap, TrackingEvent } from "@/lib/types";

declare global {
  interface Window {
    dataLayer: Record<string, unknown>[];
    eticTrack: (event: string, params?: Record<string, unknown>) => void;
    gtag?: (...args: unknown[]) => void;
    fbq?: (...args: unknown[]) => void;
  }
}

export function Tracking({
  config,
  events = [],
}: {
  config: Bootstrap["tracking"];
  events?: TrackingEvent[];
}) {
  useEffect(() => {
    window.dataLayer = window.dataLayer || [];
    window.eticTrack = (event, params = {}) => {
      window.dataLayer.push({ event, ...params });
      if (typeof window.gtag === "function") {
        window.gtag("event", event, params);
      }
      if (typeof window.fbq === "function") {
        const map: Record<string, string> = {
          view_item: "ViewContent",
          view_item_list: "ViewContent",
          view_category: "ViewContent",
          add_to_cart: "AddToCart",
          begin_checkout: "InitiateCheckout",
          add_payment_info: "AddPaymentInfo",
          purchase: "Purchase",
          search: "Search",
        };
        if (map[event]) {
          const pixelParams = { ...params };
          const eventID = pixelParams.event_id;
          delete pixelParams.event_id;
          delete pixelParams.user;
          window.fbq("track", map[event], pixelParams, eventID ? { eventID } : {});
        }
      }
    };

    events.forEach((item) => {
      const { event, ...params } = item;
      window.eticTrack(event, params);
    });
  }, [events]);

  return (
    <>
      {config.search_console_verification ? (
        <meta name="google-site-verification" content={config.search_console_verification} />
      ) : null}
      {config.gtm_container_id ? (
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${config.gtm_container_id}');`,
          }}
        />
      ) : null}
      {config.ga4_measurement_id ? (
        <>
          <script async src={`https://www.googletagmanager.com/gtag/js?id=${config.ga4_measurement_id}`} />
          <script
            dangerouslySetInnerHTML={{
              __html: `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${config.ga4_measurement_id}');`,
            }}
          />
        </>
      ) : null}
      {config.meta_pixel_id ? (
        <script
          dangerouslySetInnerHTML={{
            __html: `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${config.meta_pixel_id}');fbq('track','PageView');`,
          }}
        />
      ) : null}
    </>
  );
}

export function track(event: string, params: Record<string, unknown> = {}) {
  if (typeof window !== "undefined" && window.eticTrack) {
    window.eticTrack(event, params);
  }
}
