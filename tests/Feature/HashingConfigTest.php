<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HashingConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_hashing_config_exists_and_has_all_drivers()
    {
        $this->assertArrayHasKey('driver', config('hashing'));
        $this->assertArrayHasKey('bcrypt', config('hashing'));
        $this->assertArrayHasKey('argon', config('hashing'));
        $this->assertArrayHasKey('argon2id', config('hashing'));
    }

    public function test_default_driver_is_argon2id()
    {
        $this->assertEquals('argon2id', config('hashing.driver'));
    }

    public function test_argon2id_parameters_are_configured()
    {
        $argon = config('hashing.argon2id');
        $this->assertEquals(65536, $argon['memory']);
        $this->assertEquals(4, $argon['time']);
        $this->assertEquals(1, $argon['threads']);
    }

    public function test_config_has_documentation_rationale()
    {
        $code = file_get_contents(config_path('hashing.php'));
        $this->assertStringContainsString('OWASP', $code);
        $this->assertStringContainsString('250', $code);
        $this->assertStringContainsString('500 ms', $code);
    }

    public function test_hash_make_and_check_work()
    {
        $hash = Hash::make('test-password');
        $this->assertTrue(Hash::check('test-password', $hash));
        $this->assertFalse(Hash::check('wrong-password', $hash));
    }

    public function test_needs_rehash_detects_legacy_passwords()
    {
        $legacyHash = password_hash('legacy-password', PASSWORD_BCRYPT, ['cost' => 10]);
        $this->assertTrue(
            Hash::needsRehash($legacyHash),
            'Bcrypt with cost 10 should need rehash when argon2id is default'
        );
    }

    public function test_needs_rehash_exists_in_login_request()
    {
        $code = file_get_contents(app_path('Http/Requests/Auth/LoginRequest.php'));
        $this->assertStringContainsString('Hash::needsRehash', $code);
        $this->assertStringContainsString('Transparent re-hash', $code);
    }

    public function test_needs_rehash_exists_in_manual_auth()
    {
        $code = file_get_contents(app_path('Http/Controllers/Auth/ManualAuthController.php'));
        $this->assertStringContainsString('Hash::needsRehash', $code);
        $this->assertStringContainsString('Transparent re-hash', $code);
    }

    public function test_benchmark_script_exists()
    {
        $this->assertFileExists(base_path('database/scripts/benchmark-hashing.php'));
    }

    public function test_password_is_cast_to_hashed_on_user_model()
    {
        $user = User::factory()->create(['password' => 'test']);
        $casts = $user->getCasts();
        $this->assertEquals('hashed', $casts['password']);
    }
}
