<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ==================== index ====================

    public function test_タグ一覧が取得できる(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->getJson('/api/tags');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    // ==================== articles ====================

    public function test_タグに紐づく公開済み記事一覧が取得できる(): void
    {
        $tag = Tag::factory()->create(['slug' => 'laravel']);
        $published = Article::factory()->published()->count(2)->create();
        Article::factory()->count(1)->create(); // 下書き
        $published->each(fn ($article) => $article->tags()->attach($tag));

        $response = $this->getJson('/api/tags/laravel/articles');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_存在しないタグスラッグは404になる(): void
    {
        $response = $this->getJson('/api/tags/not-exist/articles');

        $response->assertNotFound();
    }

    // ==================== store ====================

    public function test_管理者がタグを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/tags', [
            'name' => 'Laravel',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Laravel');
        $this->assertDatabaseHas('tags', ['name' => 'Laravel']);
    }

    public function test_スラッグ未指定の場合名前から自動生成される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/tags', [
            'name' => 'My Tag',
        ]);

        $this->assertDatabaseHas('tags', ['slug' => 'my-tag']);
    }

    public function test_未認証ではタグを作成できない(): void
    {
        $response = $this->postJson('/api/tags', ['name' => 'Laravel']);

        $response->assertUnauthorized();
    }

    public function test_重複スラッグは422になる(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['slug' => 'laravel']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/tags', [
            'name' => 'Laravel2',
            'slug' => 'laravel',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['slug']);
    }

    // ==================== update ====================

    public function test_管理者がタグを更新できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/tags/{$tag->id}", [
            'name' => '更新後タグ',
        ]);

        $response->assertOk()->assertJsonPath('data.name', '更新後タグ');
    }

    public function test_未認証ではタグを更新できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/tags/{$tag->id}", ['name' => '更新後']);

        $response->assertUnauthorized();
    }

    // ==================== destroy ====================

    public function test_管理者がタグを削除できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/tags/{$tag->id}");

        $response->assertNoContent();
        $this->assertModelMissing($tag);
    }

    public function test_未認証ではタグを削除できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertUnauthorized();
    }
}
