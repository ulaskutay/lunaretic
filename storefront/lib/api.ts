import type {
  BlogPost,
  Bootstrap,
  Cart,
  CatalogResponse,
  CmsPage,
  Order,
  Paginated,
  ProductDetail,
  ShippingOption,
  TrackingEvent,
  User,
} from "./types";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public payload?: unknown,
  ) {
    super(message);
  }
}

type Options = RequestInit & {
  cartToken?: string | null;
  authToken?: string | null;
};

async function request<T>(path: string, options: Options = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (options.cartToken) {
    headers.set("X-Cart-Token", options.cartToken);
  }

  if (options.authToken) {
    headers.set("Authorization", `Bearer ${options.authToken}`);
  }

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
    cache: "no-store",
  });

  const json = await response.json().catch(() => null);

  if (!response.ok) {
    const message =
      json?.message ??
      (json?.errors ? Object.values(json.errors).flat().join(" ") : `HTTP ${response.status}`);
    throw new ApiError(String(message), response.status, json);
  }

  return json as T;
}

export function apiUrl(path: string): string {
  return `${API_URL}${path}`;
}

export const storeApi = {
  bootstrap: () => request<{ data: Bootstrap }>("/bootstrap").then((r) => r.data),

  catalog: (query = "") =>
    request<CatalogResponse>(`/products${query ? `?${query}` : ""}`),

  product: (slug: string) =>
    request<{ data: ProductDetail; schema: object; events: TrackingEvent[] }>(`/products/${slug}`),

  page: (slug: string) => request<{ data: CmsPage }>(`/pages/${slug}`).then((r) => r.data),

  blog: (query = "") =>
    request<Paginated<BlogPost> & { categories: { id: number; name: string; slug: string }[]; category: string | null }>(
      `/blog${query ? `?${query}` : ""}`,
    ),

  blogPost: (slug: string) =>
    request<{ data: BlogPost; related: BlogPost[]; schema: object }>(`/blog/${slug}`),

  cart: (token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart", { cartToken: token }),

  addToCart: (variantId: number, quantity: number, token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart", {
      method: "POST",
      body: JSON.stringify({ variant_id: variantId, quantity }),
      cartToken: token,
    }),

  updateLine: (lineId: number, quantity: number, token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart", {
      method: "PATCH",
      body: JSON.stringify({ line_id: lineId, quantity }),
      cartToken: token,
    }),

  removeLine: (lineId: number, token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart", {
      method: "DELETE",
      body: JSON.stringify({ line_id: lineId }),
      cartToken: token,
    }),

  applyCoupon: (code: string, token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart/coupon", {
      method: "POST",
      body: JSON.stringify({ code }),
      cartToken: token,
    }),

  removeCoupon: (token?: string | null) =>
    request<{ data: Cart; events: TrackingEvent[] }>("/cart/coupon", {
      method: "DELETE",
      cartToken: token,
    }),

  checkout: (token?: string | null) =>
    request<{ data: Cart; shipping_options: ShippingOption[]; events: TrackingEvent[] }>("/checkout", {
      cartToken: token,
    }),

  placeOrder: (payload: Record<string, unknown>, token?: string | null) =>
    request<{ data: Order; events: TrackingEvent[] }>("/checkout", {
      method: "POST",
      body: JSON.stringify(payload),
      cartToken: token,
    }),

  order: (id: string | number) => request<{ data: Order }>(`/orders/${id}`).then((r) => r.data),

  login: (email: string, password: string) =>
    request<{ data: { token: string; user: User } }>("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    }),

  register: (payload: { name: string; email: string; password: string; password_confirmation: string }) =>
    request<{ data: { token: string; user: User } }>("/auth/register", {
      method: "POST",
      body: JSON.stringify(payload),
    }),

  account: (authToken: string) =>
    request<{ data: { user: User; orders: Order[] } }>("/account", { authToken }),
};
