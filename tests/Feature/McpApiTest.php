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
use Illuminate\Testing\TestResponse;
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

    private function rpc(string $method, array $params = []): TestResponse
    {
        return $this->postJson('/api/mcp/rpc', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
        ], $this->auth());
    }

    public function test_rpc_initialize_and_lists(): void
    {
        $this->rpc('initialize')->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'jobs-site-mcp')
            ->assertJsonStructure(['result' => ['capabilities' => ['tools', 'resources', 'prompts']]]);

        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');
        $this->assertGreaterThanOrEqual(99, count($tools));
        $this->assertNotEmpty(collect($tools)->firstWhere('name', 'jobs_create')['inputSchema']);

        $this->rpc('resources/list')->assertOk()->assertJsonCount(11, 'result.resources');
        $this->rpc('prompts/list')->assertOk()->assertJsonCount(9, 'result.prompts');
        $this->rpc('ping')->assertOk();
    }

    public function test_rpc_tools_call_dispatches_internally(): void
    {
        $this->rpc('tools/call', ['name' => 'jobs_search', 'arguments' => ['per_page' => 1]])
            ->assertOk()->assertJsonPath('result.structuredContent.success', true);

        // Path-template substitution + body pass-through
        $job = $this->makeJob();
        $this->rpc('tools/call', ['name' => 'jobs_get', 'arguments' => ['id_or_slug' => (string) $job->id]])
            ->assertOk()->assertJsonPath('result.structuredContent.data.id', $job->id);

        // HTTP errors surface as isError, not RPC errors
        $res = $this->rpc('tools/call', ['name' => 'jobs_update', 'arguments' => ['id' => 999999, 'title' => 'x']]);
        $this->assertTrue($res->json('result.isError'));

        $this->rpc('tools/call', ['name' => 'no_such_tool'])
            ->assertOk()->assertJsonPath('result.isError', true);

        $this->rpc('unknown/method')->assertOk()->assertJsonPath('error.code', -32601);
        $this->rpc('resources/read', ['uri' => 'jobs-site://queue'])->assertOk()
            ->assertJsonStructure(['result' => ['contents' => [['uri', 'text']]]]);
        $this->rpc('prompts/get', ['name' => 'cv_review', 'arguments' => ['cv_id' => '1']])
            ->assertOk()->assertJsonPath('result.messages.0.role', 'user');
    }

    public function test_rpc_requires_token(): void
    {
        $this->postJson('/api/mcp/rpc', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertUnauthorized();
    }

    public function test_source_image_view_returns_image_block(): void
    {
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $create = $this->postJson('/api/mcp/source-images', [
            'title' => 'View Ad', 'image_base64' => $png,
        ], $this->auth())->assertCreated();
        $id = $create->json('data.id');

        $res = $this->rpc('tools/call', ['name' => 'source_image_view', 'arguments' => ['id' => $id]])
            ->assertOk();
        $content = $res->json('result.content');
        $this->assertSame('text', $content[0]['type']);
        $this->assertSame('image', $content[1]['type']);
        $this->assertSame('image/jpeg', $content[1]['mimeType']);
        $this->assertStringContainsString('part 1/1', $content[0]['text']);
        $this->assertSame("\xff\xd8", substr(base64_decode($content[1]['data']), 0, 2));

        $missing = $this->rpc('tools/call', ['name' => 'source_image_view', 'arguments' => ['id' => 999999]])
            ->assertOk();
        $this->assertTrue($missing->json('result.isError'));
    }

    public function test_bulk_delete_by_filter(): void
    {
        $expired = $this->makeJob(['deadline' => now()->subDay(), 'is_active' => true]);
        $fresh = $this->makeJob(['deadline' => now()->addMonth(), 'is_active' => true]);

        $this->deleteJson('/api/mcp/jobs/bulk-delete-by-filter', [], $this->auth())
            ->assertStatus(422);

        $this->deleteJson('/api/mcp/jobs/bulk-delete-by-filter', ['expired' => true], $this->auth())
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('matched', 1)
            ->assertJsonPath('deleted', 0);
        $this->assertDatabaseHas('job_listings', ['id' => $expired->id]);

        $this->deleteJson('/api/mcp/jobs/bulk-delete-by-filter', ['expired' => true, 'confirm' => true], $this->auth())
            ->assertOk()
            ->assertJsonPath('deleted', 1);
        $this->assertDatabaseMissing('job_listings', ['id' => $expired->id]);
        $this->assertDatabaseHas('job_listings', ['id' => $fresh->id]);
    }
}
