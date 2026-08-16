import { notFound } from "next/navigation";
import Link from "next/link";
import { storeApi } from "@/lib/api";

export default async function BlogShow({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;

  try {
    const { data: post, related } = await storeApi.blogPost(slug);

    return (
      <article className="prose max-w-2xl">
        <p className="text-sm">
          <Link href="/blog">Blog</Link>
        </p>
        <h1>{post.title}</h1>
        {post.content ? <div dangerouslySetInnerHTML={{ __html: post.content }} /> : null}
        {related.length ? (
          <div className="mt-10">
            <h2>İlgili yazılar</h2>
            <ul>
              {related.map((item) => (
                <li key={item.id}>
                  <Link href={`/blog/${item.slug}`}>{item.title}</Link>
                </li>
              ))}
            </ul>
          </div>
        ) : null}
      </article>
    );
  } catch {
    notFound();
  }
}
