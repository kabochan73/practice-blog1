'use server'

import { cookies } from 'next/headers'
import { redirect } from 'next/navigation'
import { revalidatePath } from 'next/cache'
import { api } from '@/lib/api'

export type ArticleActionState = {
  error?: string
} | null

async function getToken(): Promise<string | undefined> {
  const cookieStore = await cookies()
  return cookieStore.get('token')?.value
}

function revalidateArticleCache() {
  revalidatePath('/')
  revalidatePath('/articles/[slug]', 'page')
  revalidatePath('/tags/[slug]', 'page')
}

export async function createArticle(
  _prevState: ArticleActionState,
  formData: FormData,
): Promise<ArticleActionState> {
  const token = await getToken()
  const tagIds = formData.getAll('tags').map(Number)

  try {
    await api.post(
      '/articles',
      {
        title: formData.get('title'),
        content: formData.get('content'),
        status: formData.get('status'),
        tags: tagIds,
      },
      { token },
    )
  } catch {
    return { error: '記事の作成に失敗しました。' }
  }

  revalidateArticleCache()
  redirect('/dashboard/articles')
}

export async function updateArticle(
  id: number,
  _prevState: ArticleActionState,
  formData: FormData,
): Promise<ArticleActionState> {
  const token = await getToken()
  const tagIds = formData.getAll('tags').map(Number)

  try {
    await api.put(
      `/articles/${id}`,
      {
        title: formData.get('title'),
        content: formData.get('content'),
        status: formData.get('status'),
        tags: tagIds,
      },
      { token },
    )
  } catch {
    return { error: '記事の更新に失敗しました。' }
  }

  revalidateArticleCache()
  redirect('/dashboard/articles')
}

export async function deleteArticle(id: number): Promise<void> {
  const token = await getToken()

  await api.delete(`/articles/${id}`, { token })
  revalidateArticleCache()
  redirect('/dashboard/articles')
}
