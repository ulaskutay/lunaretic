import type { NextConfig } from "next";

const laravel = process.env.LARAVEL_URL ?? "http://localhost:8000";

const nextConfig: NextConfig = {
  experimental: {
    staleTimes: {
      dynamic: 30,
      static: 180,
    },
  },
  async redirects() {
    return [
      { source: "/p/:slug", destination: "/urun/:slug", permanent: true },
    ];
  },
  async rewrites() {
    return [
      { source: "/api/:path*", destination: `${laravel}/api/:path*` },
      { source: "/storage/:path*", destination: `${laravel}/storage/:path*` },
    ];
  },
};

export default nextConfig;
