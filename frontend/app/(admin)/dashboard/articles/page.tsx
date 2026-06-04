import Link from 'next/link'
import { cookies } from 'next/headers'
import { redirect } from 'next/navigation'
import { revalidatePath } from 'next/cache'
import { api } from '@/lib/api'
import type { ApiResponse, Article } from '@/types'

async function deleteArticle(id: number) {
  'use server'

  const cookieStore = await cookies()
  const token = cookieStore.get('token')?.value

  await api.delete(`/articles/${id}`, { token })
  revalidatePath('/')
  revalidatePath('/articles/[slug]', 'page')
  redirect('/dashboard/articles')
}

export default async function AdminArticlesPage() {
  const cookieStore = await cookies()
  const token = cookieStore.get('token')?.value

  const { data: articles } = await api.get<ApiResponse<Article[]>>('/admin/articles', {
    token,
    cache: 'no-store',
  })

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">記事管理</h1>
        <Link
          href="/dashboard/articles/new"
          className="bg-gray-900 text-white text-sm px-4 py-2 rounded-md hover:bg-gray-700"
        >
          新規作成
        </Link>
      </div>
      {articles.length === 0 ? (
        <p className="text-gray-500">記事がまだありません。</p>
      ) : (
        <table className="w-full text-sm border-collapse">
          <thead>
            <tr className="border-b border-gray-200 text-left text-gray-500">
              <th className="pb-3 font-medium">タイトル</th>
              <th className="pb-3 font-medium">ステータス</th>
              <th className="pb-3 font-medium">投稿日</th>
              <th className="pb-3 font-medium"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {articles.map((article) => (
              <tr key={article.id} className="bg-white">
                <td className="py-3 pr-4 font-medium text-gray-900">{article.title}</td>
                <td className="py-3 pr-4">
                  <span
                    className={`inline-block px-2 py-0.5 rounded text-xs font-medium ${
                      article.status === 'published'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-600'
                    }`}
                  >
                    {article.status === 'published' ? '公開' : '下書き'}
                  </span>
                </td>
                <td className="py-3 pr-4 text-gray-500">
                  {article.published_at
                    ? new Date(article.published_at).toLocaleDateString('ja-JP')
                    : '—'}
                </td>
                <td className="py-3">
                  <div className="flex items-center gap-3">
                    <Link
                      href={`/dashboard/articles/${article.id}/edit`}
                      className="text-blue-600 hover:underline"
                    >
                      編集
                    </Link>
                    <form action={deleteArticle.bind(null, article.id)}>
                      <button type="submit" className="text-red-600 hover:underline">
                        削除
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
