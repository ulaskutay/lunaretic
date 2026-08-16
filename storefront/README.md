# Etic Next.js storefront

Headless vitrin. Ticaret, CMS ve ödeme Laravel + Lunar `/api/v1` üzerinden yürür.

## Çalıştırma

Laravel API ayrı terminalde:

```bash
composer serve
```

Storefront:

```bash
cd storefront
cp .env.example .env.local
npm run dev
```

Aç: [http://localhost:3000](http://localhost:3000)

Admin ve Blade vitrin hâlâ `http://localhost:8000` üzerindedir.

## Ortam

| Değişken | Örnek |
|----------|--------|
| `NEXT_PUBLIC_API_URL` | `http://localhost:8000/api/v1` |
| `LARAVEL_URL` | `http://localhost:8000` (görseller `/storage` rewrite) |

Laravel `.env` içinde `ETIC_STOREFRONT_ORIGINS=http://localhost:3000` CORS için gerekir.

Production: repo kökündeki [DEPLOY.md](../DEPLOY.md).

