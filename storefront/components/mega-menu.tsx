import Link from "next/link";
import type { MenuItem } from "@/lib/types";
import type { Bootstrap } from "@/lib/types";

export type MegaColumn = {
  title: string | null;
  url: string | null;
  links: { label: string; url: string }[];
};

export type MegaTile = {
  label: string;
  url: string;
  image: string;
};

export function megaColumns(item: MenuItem): MegaColumn[] {
  const children = item.children ?? [];
  const groups = children.filter((child) => (child.children ?? []).length > 0);
  const leaves = children.filter((child) => !(child.children ?? []).length);
  const columns: MegaColumn[] = groups.map((group) => ({
    title: group.label,
    url: group.url,
    links: (group.children ?? []).map((link) => ({ label: link.label, url: link.url })),
  }));

  if (leaves.length) {
    columns.push({
      title: groups.length ? null : item.label,
      url: groups.length ? null : item.url,
      links: leaves.map((link) => ({ label: link.label, url: link.url })),
    });
  }

  return columns;
}

export function megaTiles(theme: Bootstrap["theme"]): MegaTile[] {
  const candidates = [
    theme.editorial?.image
      ? {
          label: theme.editorial.title || theme.editorial.kicker || "Öne çıkan",
          url: theme.editorial.cta_url || "/koleksiyon",
          image: theme.editorial.image,
        }
      : null,
    theme.editorial_secondary?.image
      ? {
          label: theme.editorial_secondary.title || theme.editorial_secondary.kicker || "Yeni",
          url: theme.editorial_secondary.cta_url || "/koleksiyon?sort=newest",
          image: theme.editorial_secondary.image,
        }
      : null,
    theme.banners?.left?.image
      ? {
          label: theme.banners.left.title || "Öne çıkan",
          url: theme.banners.left.url || "/koleksiyon",
          image: theme.banners.left.image,
        }
      : null,
    theme.banners?.right?.image
      ? {
          label: theme.banners.right.title || "Yeni",
          url: theme.banners.right.url || "/koleksiyon?sort=newest",
          image: theme.banners.right.image,
        }
      : null,
  ].filter((tile): tile is MegaTile => Boolean(tile?.image));

  const seen = new Set<string>();

  return candidates.filter((tile) => {
    if (seen.has(tile.image)) {
      return false;
    }
    seen.add(tile.image);
    return true;
  }).slice(0, 2);
}

export function MegaMenuContent({ item, tiles }: { item: MenuItem; tiles: MegaTile[] }) {
  const columns = megaColumns(item);

  return (
    <div className="etic-header__mega">
      <div className="etic-header__mega-cols">
        {columns.map((column, index) => (
          <div key={`${column.title ?? "col"}-${index}`} className="etic-header__mega-col">
            {column.title ? (
              <Link className="etic-header__mega-heading" href={column.url || "#"}>
                {column.title}
              </Link>
            ) : null}
            {column.links.map((link) => (
              <Link key={`${link.url}-${link.label}`} href={link.url}>
                {link.label}
              </Link>
            ))}
          </div>
        ))}
      </div>
      {tiles.length ? (
        <div className="etic-header__mega-tiles">
          {tiles.map((tile) => (
            <Link key={tile.image} className="etic-header__mega-tile" href={tile.url}>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={tile.image} alt={tile.label} />
              <span>{tile.label}</span>
            </Link>
          ))}
        </div>
      ) : null}
    </div>
  );
}

/** @deprecated Use MegaMenuContent inside mega-layer instead. */
export function MegaPanel({ item, tiles }: { item: MenuItem; tiles: MegaTile[] }) {
  return (
    <div className="etic-header__dropdown">
      <MegaMenuContent item={item} tiles={tiles} />
    </div>
  );
}
