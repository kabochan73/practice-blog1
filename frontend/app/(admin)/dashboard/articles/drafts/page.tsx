import Link from 'next/link'
import { cookies } from 'next/headers'
import { api } from '@/lib/api'
import type { ApiResponse, Article } from '@/types'
import { deleteArticle } from '../actions'

export default async function DraftsPage() {
  const cookieStore = await cookies()
  const token = cookieStore.get('token')?.value

  const { data: articles } = await api.get<ApiResponse<Article[]>>('/admin/articles?status=draft', {
    token,
    cache: 'no-store',
  })

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">下書き一覧</h1>
        <Link
          href="/dashboard/articles/new"
          className="bg-gray-900 text-white text-sm px-4 py-2 rounded-md hover:bg-gray-700"
        >
          新規作成
        </Link>
      </div>
      {articles.length === 0 ? (
        <p className="text-gray-500">下書きはありません。</p>
      ) : (
        <table className="w-full text-sm border-collapse">
          <thead>
            <tr className="border-b border-gray-200 text-left text-gray-500">
              <th className="pb-3 font-medium">タイトル</th>
              <th className="pb-3 font-medium">作成日</th>
              <th className="pb-3 font-medium"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {articles.map((article) => (
              <tr key={article.id} className="bg-white">
                <td className="py-3 pr-4 font-medium text-gray-900">{article.title}</td>
                <td className="py-3 pr-4 text-gray-500">
                  {new Date(article.created_at).toLocaleDateString('ja-JP')}
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
