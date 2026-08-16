import Link from "next/link";
import { storeApi } from "@/lib/api";

export default async function BlogIndex({
  searchParams,
}: {
  searchParams: Promise<{ kategori?: string }>;
}) {
  const { kategori } = await searchParams;
  const blog = await storeApi.blog(kategori ? `kategori=${kategori}` : "");

  return (
    <>
      <div className="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <h1 className="text-2xl font-semibold">Blog</h1>
        {blog.categories.length ? (
          <nav className="flex flex-wrap gap-2 text-sm">
            <Link href="/blog" className={`rounded-full px-3 py-1 ${!kategori ? "bg-neutral-900 text-white" : "bg-white"}`}>
              Tümü
            </Link>
            {blog.categories.map((category) => (
              <Link
                key={category.id}
                href={`/blog?kategori=${category.slug}`}
                className={`rounded-full px-3 py-1 ${kategori === category.slug ? "bg-neutral-900 text-white" : "bg-white"}`}
              >
                {category.name}
              </Link>
            ))}
          </nav>
        ) : null}
      </div>
      <div className="space-y-4">
        {blog.data.length ? (
          blog.data.map((post) => (
            <Link key={post.id} href={`/blog/${post.slug}`} className="block rounded-2xl bg-white p-4">
              {post.featured_image ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={post.featured_image} alt="" className="mb-3 h-40 w-full rounded-xl object-cover" />
              ) : null}
              {post.category ? <p className="text-xs uppercase text-neutral-500">{post.category.name}</p> : null}
              <h2 className="font-medium">{post.title}</h2>
              <p className="text-sm text-neutral-600">{post.excerpt}</p>
            </Link>
          ))
        ) : (
          <p className="text-sm text-neutral-600">Henüz yazı yok.</p>
        )}
      </div>
    </>
  );
}
