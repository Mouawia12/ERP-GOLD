<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiLoginModeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_device_api_login_revokes_previous_token_and_keeps_only_latest_one(): void
    {
        SystemSetting::putValue('login_mode', 'single_device');
        $user = $this->createApiUser();

        $firstLogin = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $firstLogin->assertOk();
        $firstToken = $firstLogin->json('token');
        $this->assertNotEmpty($firstToken);

        $this->withHeader('Authorization', 'Bearer ' . $firstToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        $secondLogin = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $secondLogin->assertOk();
        $secondToken = $secondLogin->json('token');

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertStringStartsWith('api:', (string) $user->fresh()->active_session_id);
        $this->assertNull(PersonalAccessToken::findToken($firstToken));
        $this->assertNotNull(PersonalAccessToken::findToken($secondToken));

        $this->withHeader('Authorization', 'Bearer ' . $secondToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_multi_device_api_login_allows_multiple_valid_tokens(): void
    {
        SystemSetting::putValue('login_mode', 'multi_device');
        $user = $this->createApiUser();

        $firstToken = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->json('token');

        $secondToken = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withHeader('Authorization', 'Bearer ' . $firstToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        $this->withHeader('Authorization', 'Bearer ' . $secondToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_inactive_user_cannot_log_in_via_api(): void
    {
        $user = $this->createApiUser([
            'status' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'User account is inactive.');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createApiUser(array $overrides = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع API', 'en' => 'API Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        return User::create(array_merge([
            'name' => 'API User',
            'email' => 'api-user-' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ], $overrides));
    }
}
