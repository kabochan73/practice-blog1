<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * タグ一覧を返す
     */
    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::all());
    }

    /**
     * タグに紐づく公開済み記事一覧を返す（タグ検索）
     */
    public function articles(string $slug): AnonymousResourceCollection
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $articles = $tag->articles()
            ->with('tags')
            ->where('status', ArticleStatus::Published)
            ->orderByDesc('published_at')
            ->get();

        return ArticleResource::collection($articles);
    }

    /**
     * タグを新規作成する（管理者のみ）
     * スラッグ未指定の場合は名前から自動生成する
     */
    public function store(StoreTagRequest $request): TagResource
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']);

        $tag = Tag::create($data);

        return new TagResource($tag);
    }

    /**
     * タグを更新する（管理者のみ）
     */
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $tag->update($request->validated());

        return new TagResource($tag);
    }

    /**
     * タグを削除する（管理者のみ）
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }
}
