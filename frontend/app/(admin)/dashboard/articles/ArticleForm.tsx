'use client'

import { useActionState } from 'react'
import { createArticle, updateArticle, type ArticleActionState } from './actions'
import type { Article, Tag } from '@/types'

type Props = {
  tags: Tag[]
  article?: Article
}

export function ArticleForm({ tags, article }: Props) {
  const action = article ? updateArticle.bind(null, article.id) : createArticle
  const [state, formAction, isPending] = useActionState<ArticleActionState, FormData>(action, null)

  return (
    <form action={formAction} className="space-y-6">
      {state?.error && (
        <p className="text-sm text-red-600 bg-red-50 px-4 py-3 rounded-md">{state.error}</p>
      )}
      <div>
        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
          タイトル <span className="text-red-500">*</span>
        </label>
        <input
          id="title"
          name="title"
          type="text"
          required
          maxLength={50}
          defaultValue={article?.title}
          className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
        />
      </div>
      <div>
        <label htmlFor="content" className="block text-sm font-medium text-gray-700 mb-1">
          本文 <span className="text-red-500">*</span>
        </label>
        <textarea
          id="content"
          name="content"
          required
          rows={12}
          defaultValue={article?.content}
          className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
        />
      </div>
      <div>
        <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
          ステータス <span className="text-red-500">*</span>
        </label>
        <select
          id="status"
          name="status"
          defaultValue={article?.status ?? 'draft'}
          className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
        >
          <option value="draft">下書き</option>
          <option value="published">公開</option>
        </select>
      </div>
      {tags.length > 0 && (
        <div>
          <p className="block text-sm font-medium text-gray-700 mb-2">タグ</p>
          <div className="flex flex-wrap gap-3">
            {tags.map((tag) => (
              <label key={tag.id} className="flex items-center gap-1.5 text-sm text-gray-700">
                <input
                  type="checkbox"
                  name="tags"
                  value={tag.id}
                  defaultChecked={article?.tags.some((t) => t.id === tag.id)}
                />
                {tag.name}
              </label>
            ))}
          </div>
        </div>
      )}
      <div className="flex gap-3">
        <button
          type="submit"
          disabled={isPending}
          className="bg-gray-900 text-white text-sm px-6 py-2 rounded-md hover:bg-gray-700 disabled:opacity-50"
        >
          {isPending ? '保存中...' : '保存'}
        </button>
        <a
          href="/dashboard/articles"
          className="text-sm px-6 py-2 rounded-md border border-gray-300 hover:bg-gray-50"
        >
          キャンセル
        </a>
      </div>
    </form>
  )
}
