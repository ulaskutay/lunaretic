import Link from "next/link";
import type { CmsPage } from "@/lib/types";

function isExternal(href: string) {
  return href.startsWith("http://") || href.startsWith("https://");
}

export function CmsPageView({ page }: { page: CmsPage }) {
  const template = page.template || "page";
  const related = page.related ?? [];
  const faq = page.faq ?? [];
  const highlights = page.highlights ?? [];
  const contacts = page.contacts ?? [];
  const cta = page.cta ?? { label: "Koleksiyonu keşfet", url: "/koleksiyon" };
  const showSidebar = ["legal", "faq", "contact"].includes(template) && related.length > 0;
  const body = page.body || page.content;

  return (
    <article className={`etic-static etic-static--${template}`}>
      <header className="etic-static__header">
        <nav className="etic-static__crumb" aria-label="Sayfa konumu">
          <Link href="/">Ana sayfa</Link>
          <span aria-hidden="true">/</span>
          <span>{page.title}</span>
        </nav>
        <p className="etic-static__kicker">{page.kicker || "Bilgi"}</p>
        <h1>{page.title}</h1>
        {template !== "story" && page.lead ? <p className="etic-static__lede">{page.lead}</p> : null}
        {template === "legal" && page.updated_at ? (
          <p className="etic-static__meta">Son güncelleme: {page.updated_at}</p>
        ) : null}
      </header>

      {template === "story" ? (
        <>
          <section className="etic-static__intro">
            <div className="etic-static__copy">
              {page.lead ? <p className="etic-static__lede">{page.lead}</p> : null}
              <Link className="etic-static__link" href={cta.url}>
                {cta.label}
              </Link>
            </div>
            <div className="etic-static__media">
              {page.image ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={page.image} alt="" />
              ) : (
                <div className="etic-static__portrait" aria-hidden="true">
                  <span>{page.brand || "Etic"}</span>
                </div>
              )}
            </div>
          </section>

          {highlights.length ? (
            <section className="etic-static__highlights" aria-label="Alışveriş avantajları">
              {highlights.map((item, index) => (
                <article key={`${item.title}-${index}`}>
                  <span>{String(index + 1).padStart(2, "0")}</span>
                  <h2>{item.title}</h2>
                  <p>{item.description}</p>
                </article>
              ))}
            </section>
          ) : null}
        </>
      ) : null}

      {template === "contact" && contacts.length ? (
        <section className="etic-static__contacts" aria-label="İletişim kanalları">
          {contacts.map((item) => {
            const inner = (
              <>
                <p>{item.label}</p>
                <strong>{item.value}</strong>
                <span>{item.hint}</span>
              </>
            );

            if (!item.href) {
              return (
                <div className="etic-static__card" key={item.label}>
                  {inner}
                </div>
              );
            }

            if (isExternal(item.href)) {
              return (
                <a className="etic-static__card" key={item.label} href={item.href} rel="noreferrer" target="_blank">
                  {inner}
                </a>
              );
            }

            return (
              <Link className="etic-static__card" key={item.label} href={item.href}>
                {inner}
              </Link>
            );
          })}
        </section>
      ) : null}

      <div className={showSidebar ? "etic-static__layout" : undefined}>
        {showSidebar ? (
          <aside className="etic-static__nav" aria-label="Yardım sayfaları">
            <p>Yardım sayfaları</p>
            {related.map((item) => (
              <Link key={item.slug} href={item.url} className={item.current ? "is-active" : undefined}>
                {item.title}
              </Link>
            ))}
          </aside>
        ) : null}

        <div className="etic-static__main">
          {template === "faq" && faq.length ? (
            <div className="etic-static__faq">
              {faq.map((item, index) => (
                <details key={item.question} open={index === 0}>
                  <summary>{item.question}</summary>
                  <div className="etic-static__prose" dangerouslySetInnerHTML={{ __html: item.answer }} />
                </details>
              ))}
            </div>
          ) : body ? (
            <div className="etic-static__prose" dangerouslySetInnerHTML={{ __html: body }} />
          ) : null}
        </div>
      </div>

      {template === "story" ? (
        <section className="etic-static__cta">
          <p>Seçkimizi keşfedin.</p>
          <Link href={cta.url}>{cta.label}</Link>
        </section>
      ) : null}
    </article>
  );
}
