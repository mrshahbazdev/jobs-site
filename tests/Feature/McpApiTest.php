<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-mcp-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['mcp.token' => self::TOKEN]);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN];
    }

    public function test_routes_are_hidden_when_token_not_configured(): void
    {
        config(['mcp.token' => null]);
        $this->getJson('/api/mcp/health')->assertNotFound();
    }

    public function test_requests_without_valid_token_are_rejected(): void
    {
        $this->getJson('/api/mcp/health')->assertUnauthorized();
        $this->getJson('/api/mcp/health', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
        $this->getJson('/api/mcp/health', ['X-MCP-Token' => self::TOKEN])->assertOk();
    }

    public function test_health_reports_counts(): void
    {
        $this->getJson('/api/mcp/health', $this->auth())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.database.ok', true)
            ->assertJsonStructure(['data' => ['app', 'drivers', 'integrations', 'counts', 'scrapers']]);
    }

    public function test_sql_is_read_only(): void
    {
        $this->postJson('/api/mcp/sql', ['query' => 'select 1 as one'], $this->auth())
            ->assertOk()
            ->assertJsonPath('rows.0.one', 1);

        $this->postJson('/api/mcp/sql', ['query' => 'delete from users'], $this->auth())
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/mcp/sql', ['query' => 'select 1; drop table users'], $this->auth())
            ->assertStatus(422);
    }

    public function test_artisan_is_allow_listed(): void
    {
        $this->postJson('/api/mcp/artisan', ['command' => 'db:wipe'], $this->auth())
            ->assertStatus(422);

        $this->postJson('/api/mcp/artisan', ['command' => 'about', 'arguments' => ['--json' => true]], $this->auth())
            ->assertOk()
            ->assertJsonPath('exit_code', 0);

        $this->postJson('/api/mcp/artisan', ['command' => 'about', 'arguments' => ['--env' => 'production']], $this->auth())
            ->assertStatus(422);
    }

    public function test_deactivate_expired_jobs(): void
    {
        $category = Category::create(['name' => 'Gov', 'slug' => 'gov']);
        $city = City::create(['name' => 'Lahore', 'slug' => 'lahore']);
        $expired = JobListing::create([
            'title' => 'Old job', 'slug' => 'old-job', 'category_id' => $category->id, 'city_id' => $city->id,
            'description_html' => '<p>x</p>', 'deadline' => now()->subDays(3)->toDateString(), 'is_active' => true,
        ]);
        JobListing::create([
            'title' => 'Fresh job', 'slug' => 'fresh-job', 'category_id' => $category->id, 'city_id' => $city->id,
            'description_html' => '<p>x</p>', 'deadline' => now()->addDays(3)->toDateString(), 'is_active' => true,
        ]);

        $this->postJson('/api/mcp/jobs/deactivate-expired', ['dry_run' => true], $this->auth())
            ->assertOk()->assertJsonPath('affected', 1);
        $this->assertEquals(1, $expired->fresh()->is_active);

        $this->postJson('/api/mcp/jobs/deactivate-expired', [], $this->auth())
            ->assertOk()->assertJsonPath('affected', 1);
        $this->assertEquals(0, $expired->fresh()->is_active);
    }

    public function test_settings_upsert_and_delete(): void
    {
        $this->putJson('/api/mcp/settings', ['settings' => [['key' => 'ad_test', 'value' => '<b>x</b>']]], $this->auth())
            ->assertOk()->assertJsonPath('updated', 1);
        $this->getJson('/api/mcp/settings', $this->auth())
            ->assertOk()->assertJsonFragment(['key' => 'ad_test']);
        $this->deleteJson('/api/mcp/settings/ad_test', [], $this->auth())
            ->assertOk()->assertJsonPath('deleted', 1);
    }

    public function test_post_crud(): void
    {
        $created = $this->postJson('/api/mcp/posts', [
            'title' => 'Hello MCP', 'content' => '<p>Body text here</p>', 'is_published' => true,
        ], $this->auth())->assertCreated()->assertJsonPath('data.slug', 'hello-mcp');

        $id = $created->json('data.id');
        $this->getJson('/api/mcp/posts/hello-mcp', $this->auth())->assertOk()->assertJsonPath('data.id', $id);
        $this->putJson("/api/mcp/posts/{$id}", ['is_published' => false], $this->auth())
            ->assertOk()->assertJsonPath('data.is_published', false);
        $this->deleteJson("/api/mcp/posts/{$id}", [], $this->auth())->assertOk();
        $this->getJson("/api/mcp/posts/{$id}", $this->auth())->assertNotFound();
    }
}
