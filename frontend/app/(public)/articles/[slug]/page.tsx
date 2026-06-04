import Link from 'next/link'
import { notFound } from 'next/navigation'
import { api } from '@/lib/api'
import type { ApiResponse, Article } from '@/types'

// ISRを有効にするため空配列を返す（初回アクセス時に生成・キャッシュされる）
export async function generateStaticParams() {
  return []
}

export default async function ArticlePage({
  params,
}: {
  params: Promise<{ slug: string }>
}) {
  const { slug } = await params

  let article: Article

  try {
    const res = await api.get<ApiResponse<Article>>(`/articles/${slug}`, {
      cache: 'force-cache',
    })
    article = res.data
  } catch {
    notFound()
  }

  return (
    <article>
      <header className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-3">{article.title}</h1>
        <p className="text-sm text-gray-500 mb-4">
          {article.published_at
            ? new Date(article.published_at).toLocaleDateString('ja-JP')
            : ''}
        </p>
        {article.tags.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {article.tags.map((tag) => (
              <Link
                key={tag.id}
                href={`/tags/${tag.slug}`}
                className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200"
              >
                {tag.name}
              </Link>
            ))}
          </div>
        )}
      </header>
      <div className="prose prose-gray max-w-none whitespace-pre-wrap text-gray-800 leading-8">
        {article.content}
      </div>
    </article>
  )
}
