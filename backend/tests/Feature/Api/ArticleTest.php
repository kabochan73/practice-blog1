<?php

namespace Tests\Feature\Api;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ==================== index ====================

    public function test_公開済み記事の一覧が取得できる(): void
    {
        Article::factory()->published()->count(3)->create();
        Article::factory()->count(2)->create(); // 下書き

        $response = $this->getJson('/api/articles');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_記事一覧にタグが含まれる(): void
    {
        $article = Article::factory()->published()->create();
        $tag = Tag::factory()->create();
        $article->tags()->attach($tag);

        $response = $this->getJson('/api/articles');

        $response->assertOk()
            ->assertJsonPath('data.0.tags.0.id', $tag->id);
    }

    // ==================== show ====================

    public function test_スラッグで公開済み記事の詳細が取得できる(): void
    {
        Article::factory()->published()->create(['slug' => 'test-article']);

        $response = $this->getJson('/api/articles/test-article');

        $response->assertOk()->assertJsonPath('data.slug', 'test-article');
    }

    public function test_下書き記事は詳細取得で404になる(): void
    {
        Article::factory()->create(['slug' => 'draft-article']);

        $response = $this->getJson('/api/articles/draft-article');

        $response->assertNotFound();
    }

    public function test_存在しないスラッグは404になる(): void
    {
        $response = $this->getJson('/api/articles/not-exist');

        $response->assertNotFound();
    }

    // ==================== adminShow ====================

    public function test_管理者がIDで記事詳細を取得できる(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/admin/articles/{$article->id}");

        $response->assertOk()->assertJsonPath('data.id', $article->id);
    }

    public function test_管理者が下書き記事をIDで取得できる(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/admin/articles/{$article->id}");

        $response->assertOk()->assertJsonPath('data.status', 'draft');
    }

    public function test_未認証では管理用記事詳細を取得できない(): void
    {
        $article = Article::factory()->create();

        $response = $this->getJson("/api/admin/articles/{$article->id}");

        $response->assertUnauthorized();
    }

    // ==================== store ====================

    public function test_管理者が記事を作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/articles', [
            'title' => 'テスト記事',
            'content' => '本文です。',
            'status' => ArticleStatus::Draft->value,
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'テスト記事');
        $this->assertDatabaseHas('articles', ['title' => 'テスト記事']);
    }

    public function test_スラッグ未指定の場合タイトルから自動生成される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/articles', [
            'title' => 'My New Post',
            'content' => '本文です。',
            'status' => ArticleStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('articles', ['slug' => 'my-new-post']);
    }

    public function test_公開ステータスで作成するとpublished_atが自動セットされる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/articles', [
            'title' => 'テスト記事',
            'content' => '本文です。',
            'status' => ArticleStatus::Published->value,
        ]);

        $this->assertDatabaseMissing('articles', ['published_at' => null]);
    }

    public function test_記事作成時にタグを紐付けられる(): void
    {
        $user = User::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/articles', [
            'title' => 'テスト記事',
            'content' => '本文です。',
            'status' => ArticleStatus::Draft->value,
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $article = Article::first();
        $this->assertCount(2, $article->tags);
    }

    public function test_未認証では記事を作成できない(): void
    {
        $response = $this->postJson('/api/articles', [
            'title' => 'テスト記事',
            'content' => '本文です。',
            'status' => ArticleStatus::Draft->value,
        ]);

        $response->assertUnauthorized();
    }

    public function test_タイトルが50文字を超えると422になる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/articles', [
            'title' => str_repeat('あ', 51),
            'content' => '本文です。',
            'status' => ArticleStatus::Draft->value,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['title']);
    }

    // ==================== update ====================

    public function test_管理者が記事を更新できる(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/articles/{$article->id}", [
            'title' => '更新後タイトル',
        ]);

        $response->assertOk()->assertJsonPath('data.title', '更新後タイトル');
    }

    public function test_初めてpublishedに変更するとpublished_atがセットされる(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => ArticleStatus::Draft, 'published_at' => null]);

        $this->actingAs($user, 'sanctum')->putJson("/api/articles/{$article->id}", [
            'status' => ArticleStatus::Published->value,
        ]);

        $this->assertNotNull($article->fresh()->published_at);
    }

    public function test_未認証では記事を更新できない(): void
    {
        $article = Article::factory()->create();

        $response = $this->putJson("/api/articles/{$article->id}", ['title' => '更新後']);

        $response->assertUnauthorized();
    }

    // ==================== adminIndex ====================

    public function test_管理者が下書きを含む全記事一覧を取得できる(): void
    {
        $user = User::factory()->create();
        Article::factory()->published()->count(2)->create();
        Article::factory()->count(3)->create(); // 下書き

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/articles');

        $response->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_statusパラメータで下書きのみ取得できる(): void
    {
        $user = User::factory()->create();
        Article::factory()->published()->count(2)->create();
        Article::factory()->count(3)->create(); // 下書き

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/articles?status=draft');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_statusパラメータで公開済みのみ取得できる(): void
    {
        $user = User::factory()->create();
        Article::factory()->published()->count(2)->create();
        Article::factory()->count(3)->create(); // 下書き

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/articles?status=published');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_未認証では管理用記事一覧を取得できない(): void
    {
        $response = $this->getJson('/api/admin/articles');

        $response->assertUnauthorized();
    }

    // ==================== destroy ====================

    public function test_管理者が記事を削除できる(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/articles/{$article->id}");

        $response->assertNoContent();
        $this->assertModelMissing($article);
    }

    public function test_未認証では記事を削除できない(): void
    {
        $article = Article::factory()->create();

        $response = $this->deleteJson("/api/articles/{$article->id}");

        $response->assertUnauthorized();
    }
}
