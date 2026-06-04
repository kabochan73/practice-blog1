import Link from 'next/link'
import { notFound } from 'next/navigation'
import { api } from '@/lib/api'
import type { ApiResponse, Article, Tag } from '@/types'

export async function generateStaticParams() {
  return []
}

export default async function TagPage({
  params,
}: {
  params: Promise<{ slug: string }>
}) {
  const { slug } = await params

  let articles: Article[]
  let tags: Tag[]

  try {
    const [articlesRes, tagsRes] = await Promise.all([
      api.get<ApiResponse<Article[]>>(`/tags/${slug}/articles`, { cache: 'force-cache' }),
      api.get<ApiResponse<Tag[]>>('/tags', { cache: 'force-cache' }),
    ])
    articles = articlesRes.data
    tags = tagsRes.data
  } catch {
    notFound()
  }

  const tag = tags.find((t) => t.slug === slug)
  if (!tag) notFound()

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-2">
        タグ：{tag.name}
      </h1>
      <p className="text-sm text-gray-500 mb-8">{articles.length}件の記事</p>
      {articles.length === 0 ? (
        <p className="text-gray-500">このタグの記事はまだありません。</p>
      ) : (
        <ul className="space-y-6">
          {articles.map((article) => (
            <li key={article.id} className="bg-white rounded-lg border border-gray-200 p-6">
              <Link href={`/articles/${article.slug}`} className="hover:opacity-75">
                <h2 className="text-xl font-semibold text-gray-900 mb-2">{article.title}</h2>
              </Link>
              <p className="text-sm text-gray-500 mb-3">
                {article.published_at
                  ? new Date(article.published_at).toLocaleDateString('ja-JP')
                  : ''}
              </p>
              {article.tags.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {article.tags.map((t) => (
                    <Link
                      key={t.id}
                      href={`/tags/${t.slug}`}
                      className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200"
                    >
                      {t.name}
                    </Link>
                  ))}
                </div>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
