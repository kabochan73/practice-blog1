import Link from 'next/link'
import { api } from '@/lib/api'
import type { ApiResponse, Article, Tag } from '@/types'

export default async function Home() {
  const [{ data: articles }, { data: tags }] = await Promise.all([
    api.get<ApiResponse<Article[]>>('/articles', { cache: 'force-cache' }),
    api.get<ApiResponse<Tag[]>>('/tags', { cache: 'force-cache' }),
  ])

  return (
    <div className="flex gap-8">
      <main className="flex-1 min-w-0">
        <h1 className="text-2xl font-bold text-gray-900 mb-8">記事一覧</h1>
        {articles.length === 0 ? (
          <p className="text-gray-500">記事がまだありません。</p>
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
              </li>
            ))}
          </ul>
        )}
      </main>
      <aside className="w-48 shrink-0">
        <h2 className="text-sm font-semibold text-gray-700 mb-3">タグ</h2>
        {tags.length === 0 ? (
          <p className="text-xs text-gray-400">タグがありません</p>
        ) : (
          <ul className="space-y-1">
            {tags.map((tag) => (
              <li key={tag.id}>
                <Link
                  href={`/tags/${tag.slug}`}
                  className="text-sm text-gray-600 hover:text-gray-900 hover:underline"
                >
                  {tag.name}
                </Link>
              </li>
            ))}
          </ul>
        )}
      </aside>
    </div>
  )
}
