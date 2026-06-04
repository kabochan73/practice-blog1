<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /**
     * 公開済み記事の一覧を返す（公開側用）
     */
    public function index(): AnonymousResourceCollection
    {
        $articles = Article::with('tags')
            ->where('status', ArticleStatus::Published)
            ->orderByDesc('published_at')
            ->get();

        return ArticleResource::collection($articles);
    }

    /**
     * 全記事の一覧を返す（管理側用）
     * ?status=draft または ?status=published でフィルタ可能
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $query = Article::with('tags')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ArticleResource::collection($query->get());
    }

    /**
     * IDで記事の詳細を返す（管理側用・下書き含む）
     */
    public function adminShow(Article $article): ArticleResource
    {
        return new ArticleResource($article->load('tags'));
    }

    /**
     * スラッグで公開済み記事の詳細を返す（公開側用）
     */
    public function show(string $slug): ArticleResource
    {
        $article = Article::with('tags')
            ->where('status', ArticleStatus::Published)
            ->where('slug', $slug)
            ->firstOrFail();

        return new ArticleResource($article);
    }

    /**
     * 記事を新規作成する（管理者のみ）
     * スラッグ未指定の場合はタイトルから自動生成する
     * publishedステータスかつpublished_at未指定の場合は現在時刻をセットする
     */
    public function store(StoreArticleRequest $request): ArticleResource
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['title']) ?: Str::uuid()->toString();

        if ($data['status'] === ArticleStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $article = Article::create($data);
        $article->tags()->sync($data['tags'] ?? []);

        return new ArticleResource($article->load('tags'));
    }

    /**
     * 記事を更新する（管理者のみ）
     * 初めてpublishedに変更された場合はpublished_atを現在時刻にセットする
     */
    public function update(UpdateArticleRequest $request, Article $article): ArticleResource
    {
        $data = $request->validated();

        if (isset($data['status']) && $data['status'] === ArticleStatus::Published->value
            && empty($article->published_at) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $article->update($data);

        if (array_key_exists('tags', $data)) {
            $article->tags()->sync($data['tags'] ?? []);
        }

        return new ArticleResource($article->load('tags'));
    }

    /**
     * 記事を削除する（管理者のみ）
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(null, 204);
    }
}
