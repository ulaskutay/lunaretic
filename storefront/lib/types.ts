export type Money = {
  value: number;
  formatted: string;
};

export type MenuItem = {
  id: number;
  label: string;
  url: string;
  children: MenuItem[];
};

export type ThemeHero = {
  enabled?: boolean;
  kicker: string | null;
  title: string | null;
  cta_primary: string | null;
  cta_primary_url: string | null;
  cta_secondary: string | null;
  cta_secondary_url: string | null;
  image: string | null;
};

export type ThemeFeatured = {
  enabled?: boolean;
  title: string | null;
};

export type ThemeBestSellers = {
  enabled?: boolean;
  title: string | null;
  cta: string | null;
  url: string;
};

export type ThemeBanner = {
  image: string | null;
  title: string | null;
  subtitle: string | null;
  cta: string | null;
  url: string | null;
};

export type ThemeBanners = {
  enabled?: boolean;
  left: ThemeBanner;
  right: ThemeBanner;
};

export type ThemeCountdown = {
  enabled?: boolean;
  title: string | null;
  description: string | null;
  ends_at: string | null;
};

export type ThemeNewsletter = {
  enabled?: boolean;
  kicker: string | null;
  title: string | null;
  description: string | null;
  placeholder: string | null;
  cta: string | null;
  benefits: {
    title: string | null;
    description: string | null;
  }[];
};

export type ThemeShopLook = {
  enabled?: boolean;
  kicker: string | null;
  title: string | null;
  image: string | null;
  hotspots: {
    product_id: number | null;
    x: number;
    y: number;
    product: ProductCard;
  }[];
};

export type ThemeEditorial = {
  enabled?: boolean;
  kicker: string | null;
  title: string | null;
  cta: string | null;
  cta_url: string | null;
  image: string | null;
};

export type Theme = {
  handle: string;
  name: string;
  logo_text: string;
  logo: string | null;
  favicon: string | null;
  announcement: string | null;
  header_style: string;
  container: string;
  footer_text: string | null;
  footer_image?: string | null;
  newsletter?: ThemeNewsletter;
  social: {
    instagram: string | null;
    tiktok: string | null;
    facebook: string | null;
    whatsapp: string | null;
  };
  hero?: ThemeHero;
  featured?: ThemeFeatured;
  editorial?: ThemeEditorial;
  editorial_secondary?: ThemeEditorial;
  best_sellers?: ThemeBestSellers;
  banners?: ThemeBanners;
  shop_look?: ThemeShopLook;
  countdown?: ThemeCountdown;
  css_variables: Record<string, string>;
};

export type Bootstrap = {
  store: {
    name: string;
    handle: string;
    locale: string;
    currency: string;
  };
  menus: {
    header: MenuItem[];
    footer: MenuItem[];
  };
  tracking: {
    ga4_measurement_id: string | null;
    gtm_container_id: string | null;
    meta_pixel_id: string | null;
    search_console_verification: string | null;
  };
  theme: Theme;
};

export type ColorVariant = {
  id: number;
  name: string | null;
  slug: string | null;
  image: string | null;
  color: string | null;
  active: boolean;
};

export type ProductCard = {
  id: number;
  name: string;
  slug: string;
  status: string;
  image: string | null;
  price: Money | null;
  compare_price: Money | null;
  brand: string | null;
  in_stock: boolean;
  color_variants: ColorVariant[];
};

export type Variant = {
  id: number;
  sku: string;
  stock: number;
  purchasable: boolean;
  price: Money | null;
  compare_price: Money | null;
  values: { id: number; name: string; option: string | null }[];
};

export type GalleryImage = {
  src: string;
  thumb: string;
  zoom: string;
};

export type ProductDetail = ProductCard & {
  description: string | null;
  gallery: string[];
  gallery_items?: GalleryImage[];
  collections?: CollectionCard[];
  variants: Variant[];
};

export type Facets = {
  colors: { id: number; name: string }[];
  sizes: { id: number; name: string }[];
  brands: { id: number; name: string }[];
};

export type CollectionCard = {
  id: number;
  name: string;
  slug: string;
};

export type Paginated<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type CatalogResponse = Paginated<ProductCard> & {
  facets: Facets;
  collections: CollectionCard[];
  collection: CollectionCard | null;
  events: TrackingEvent[];
};

export type CartLine = {
  id: number;
  sku: string;
  quantity: number;
  name: string | null;
  slug: string | null;
  image: string | null;
  unit_price: Money | null;
  total: Money | null;
  values: { id: number; name: string; option: string | null }[];
};

export type Cart = {
  id: number;
  token: string | null;
  coupon_code: string | null;
  lines: CartLine[];
  subtotal: Money | null;
  discount_total: Money | null;
  shipping_total: Money | null;
  tax_total: Money | null;
  total: Money | null;
  currency: string;
  free_shipping?: {
    threshold: number | null;
    remaining: number;
    unlocked: boolean;
  };
};

export type ShippingOption = {
  identifier: string;
  name: string;
  description: string | null;
  price: Money | null;
};

export type Order = {
  id: number;
  reference: string | null;
  status: string;
  status_label: string;
  total: Money | null;
  currency: string;
};

export type TrackingEvent = {
  event: string;
  [key: string]: unknown;
};

export type CmsPage = {
  id: number;
  title: string;
  slug: string;
  template?: "page" | "story" | "legal" | "contact" | "faq";
  kicker?: string | null;
  lead?: string | null;
  body?: string | null;
  content: string | null;
  image?: string | null;
  brand?: string | null;
  updated_at?: string | null;
  faq?: { question: string; answer: string }[];
  related?: { title: string; slug: string; url: string; current?: boolean }[];
  highlights?: { title: string; description: string }[];
  contacts?: { label: string; value: string; href: string | null; hint: string }[];
  cta?: { label: string; url: string };
  seo: Seo | null;
};

export type BlogPost = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content?: string | null;
  author: string | null;
  published_at: string | null;
  featured_image: string | null;
  category: { id: number; name: string; slug: string } | null;
  seo: Seo | null;
};

export type Seo = {
  title: string | null;
  description: string | null;
  robots: string | null;
  og_title: string | null;
  og_description: string | null;
  og_image: string | null;
};

export type User = {
  id: number;
  name: string;
  email: string;
};
