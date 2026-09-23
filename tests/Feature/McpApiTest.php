<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    private function makeJob(array $overrides = []): JobListing
    {
        static $n = 0;
        $n++;
        $category = Category::firstOrCreate(['slug' => 'gov'], ['name' => 'Gov']);
        $city = City::firstOrCreate(['slug' => 'lahore'], ['name' => 'Lahore']);

        return JobListing::create([
            'title' => "Job {$n}", 'slug' => "job-{$n}", 'category_id' => $category->id, 'city_id' => $city->id,
            'description_html' => '<p>x</p>', 'is_active' => true,
        ] + $overrides);
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

    public function test_user_crud_and_reset_password(): void
    {
        $create = $this->postJson('/api/mcp/users', [
            'name' => 'MCP User', 'email' => 'mcp@example.com', 'role' => 'seeker', 'verified' => true,
        ], $this->auth())->assertCreated();
        $id = $create->json('data.id');
        $this->assertNotEmpty($create->json('generated_password'));
        $this->assertNotNull($create->json('data.email_verified_at'));

        $this->getJson("/api/mcp/users/{$id}", $this->auth())->assertOk()->assertJsonPath('data.email', 'mcp@example.com');
        $this->putJson("/api/mcp/users/{$id}", ['role' => 'employer'], $this->auth())
            ->assertOk()->assertJsonPath('data.role', 'employer');
        $this->postJson("/api/mcp/users/{$id}/reset-password", ['password' => 'secret12345'], $this->auth())
            ->assertOk()->assertJsonPath('generated_password', null);
        $this->deleteJson("/api/mcp/users/{$id}", [], $this->auth())->assertOk();
        $this->getJson("/api/mcp/users/{$id}", $this->auth())->assertNotFound();
    }

    public function test_bookmarks_toggle_and_stats(): void
    {
        $user = User::factory()->create();
        $job = $this->makeJob();

        $this->postJson('/api/mcp/bookmarks/toggle', ['user_id' => $user->id, 'job_id' => $job->id], $this->auth())
            ->assertOk()->assertJsonPath('bookmarked', true);
        $this->getJson('/api/mcp/bookmarks', $this->auth())->assertOk()
            ->assertJsonPath('summary.total', 1);
        $this->postJson('/api/mcp/bookmarks/toggle', ['user_id' => $user->id, 'job_id' => $job->id], $this->auth())
            ->assertOk()->assertJsonPath('bookmarked', false);
    }

    public function test_source_image_ingest_publish_delete(): void
    {
        $job = $this->makeJob();
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $create = $this->postJson('/api/mcp/source-images', [
            'title' => 'Test Ad', 'image_base64' => $png, 'article_text' => 'ad text',
        ], $this->auth())->assertCreated();
        $id = $create->json('data.id');
        $this->assertStringContainsString('storage/', $create->json('data.image_url'));

        $this->getJson("/api/mcp/source-images/{$id}", $this->auth())->assertOk()
            ->assertJsonPath('data.publish_status', 'pending');
        $this->postJson("/api/mcp/source-images/{$id}/publish", ['job_id' => $job->id], $this->auth())
            ->assertOk()->assertJsonPath('data.publish_status', 'published');
        $this->assertEquals($id, $job->fresh()->job_source_image_id);

        $this->deleteJson("/api/mcp/source-images/{$id}", [], $this->auth())->assertOk();
        $this->assertNull(JobListing::find($job->id)->job_source_image_id);
    }

    public function test_sitemaps_and_flush(): void
    {
        $this->getJson('/api/mcp/sitemaps', $this->auth())->assertOk()
            ->assertJsonStructure(['urls', 'job_sitemap_pages', 'cached']);
        $this->postJson('/api/mcp/sitemaps/flush', [], $this->auth())->assertOk()->assertJson(['success' => true]);
    }

    public function test_alerts_preview_and_send_empty(): void
    {
        Subscriber::create([
            'name' => 'Sub', 'email_or_whatsapp' => 'sub@example.com', 'is_active' => true,
        ]);
        $res = $this->getJson('/api/mcp/alerts/preview', $this->auth())->assertOk();
        $this->assertEquals(1, $res->json('active_subscribers'));
        $this->assertEquals(0, $res->json('would_send')); // no jobs in window
        $this->postJson('/api/mcp/alerts/send', ['subscriber_ids' => [999]], $this->auth())
            ->assertOk()->assertJson(['sent' => []]);
    }

    public function test_mail_test_and_storage(): void
    {
        Mail::fake();
        $this->postJson('/api/mcp/mail/test', ['to' => 'a@example.com'], $this->auth())->assertOk();

        Storage::disk('public')->put('job-sources/orphan.txt', 'x');
        $res = $this->getJson('/api/mcp/storage?dir=job-sources', $this->auth())->assertOk();
        $this->assertContains('job-sources/orphan.txt', array_column($res->json('files'), 'path'));
        $orphans = $this->getJson('/api/mcp/storage/orphans', $this->auth())->assertOk();
        $this->assertContains('job-sources/orphan.txt', $orphans->json('orphan_files'));
        $this->postJson('/api/mcp/storage/delete', ['paths' => ['job-sources/orphan.txt']], $this->auth())
            ->assertOk()->assertJsonPath('deleted', ['job-sources/orphan.txt']);
    }

    public function test_mcp_routes_stay_up_during_maintenance(): void
    {
        $this->postJson('/api/mcp/artisan', ['command' => 'down', 'arguments' => ['--secret' => 'bypass-me']], $this->auth())->assertOk();
        try {
            $this->getJson('/api/mcp/health', $this->auth())->assertOk();
        } finally {
            $this->postJson('/api/mcp/artisan', ['command' => 'up', 'arguments' => []], $this->auth())->assertOk();
        }
    }
}
