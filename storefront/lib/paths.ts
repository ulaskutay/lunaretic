export function productPath(slug: string | null | undefined): string {
  return `/urun/${slug ?? ""}`;
}
