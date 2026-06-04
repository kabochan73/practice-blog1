import { api } from '@/lib/api'
import type { ApiResponse, Article, Tag } from '@/types'
import { SearchableArticleList } from './SearchableArticleList'

export default async function Home() {
  const [{ data: articles }, { data: tags }] = await Promise.all([
    api.get<ApiResponse<Article[]>>('/articles', { cache: 'force-cache' }),
    api.get<ApiResponse<Tag[]>>('/tags', { cache: 'force-cache' }),
  ])

  return <SearchableArticleList articles={articles} tags={tags} />
}
