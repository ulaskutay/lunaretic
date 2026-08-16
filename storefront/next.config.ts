import type { NextConfig } from "next";

const laravel = process.env.LARAVEL_URL ?? "http://localhost:8000";

const nextConfig: NextConfig = {
  async redirects() {
    return [
      { source: "/p/:slug", destination: "/urun/:slug", permanent: true },
    ];
  },
  async rewrites() {
    return [
      { source: "/storage/:path*", destination: `${laravel}/storage/:path*` },
    ];
  },
};

export default nextConfig;
