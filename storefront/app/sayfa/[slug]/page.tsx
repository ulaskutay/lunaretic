import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { storeApi } from "@/lib/api";
import { CmsPageView } from "@/components/cms-page";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;

  try {
    const page = await storeApi.page(slug);

    return {
      title: page.seo?.title || page.title,
      description: page.seo?.description || page.lead || undefined,
      robots: page.seo?.robots || "index,follow",
    };
  } catch {
    return { title: "Sayfa bulunamadı" };
  }
}

export default async function CmsPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;

  try {
    const page = await storeApi.page(slug);

    return <CmsPageView page={page} />;
  } catch {
    notFound();
  }
}
