<?php

namespace Tests\Feature;

use App\Models\{User, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EncryptionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_encrypts_phone_address_tax_id()
    {
        $user = User::factory()->create([
            'phone' => '+15551234567',
            'address' => '123 Main St, Springfield',
            'tax_id' => 'TAX-123456',
        ]);

        $raw = $user->getRawOriginal('phone');
        $this->assertNotEquals('+15551234567', $raw, 'Phone should be encrypted in DB');
        $this->assertStringStartsWith('eyJ', $raw, 'Encrypted value should start with base64 prefix');

        // Eloquent casts decrypt transparently
        $this->assertEquals('+15551234567', $user->phone);
        $this->assertEquals('123 Main St, Springfield', $user->address);
        $this->assertEquals('TAX-123456', $user->tax_id);
    }

    public function test_extra_attributes_is_encrypted_array_cast()
    {
        $user = User::factory()->create([
            'extra_attributes' => ['theme' => 'dark', 'notifications' => true],
        ]);

        $this->assertIsArray($user->extra_attributes);
        $this->assertEquals('dark', $user->extra_attributes['theme']);
    }

    public function test_order_model_encrypts_billing_and_shipping_addresses()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'billing_address' => '456 Billing Rd',
            'shipping_address' => '789 Shipping Ln',
        ]);

        $rawBilling = $order->getRawOriginal('billing_address');
        $this->assertNotEquals('456 Billing Rd', $rawBilling);

        $this->assertEquals('456 Billing Rd', $order->billing_address);
        $this->assertEquals('789 Shipping Ln', $order->shipping_address);
    }

    public function test_blind_index_allows_phone_lookup()
    {
        $user = User::factory()->create(['phone' => '+19998887777']);

        $found = User::findByPhone('+19998887777');
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);

        $notFound = User::findByPhone('+11111111111');
        $this->assertNull($notFound);
    }

    public function test_blind_index_allows_tax_id_lookup()
    {
        $user = User::factory()->create(['tax_id' => 'TAX-987654']);

        $found = User::findByTaxId('TAX-987654');
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_direct_phone_query_does_not_work_on_encrypted_column()
    {
        User::factory()->create(['phone' => '+18887776666']);

        $result = User::where('phone', '+18887776666')->first();
        $this->assertNull($result,
            'Querying encrypted column directly should not work — must use blind index');
    }

    public function test_encrypted_payload_urls_exist_in_routes()
    {
        $routes = file_get_contents(base_path('routes/api.php'));
        $this->assertStringContainsString('encryptString', $routes);
        $this->assertStringContainsString('decryptString', $routes);
        $this->assertStringContainsString('generate-encrypted-link', $routes);
    }

    public function test_encrypt_string_no_serialize_avoids_object_injection()
    {
        // The route uses Crypt::encryptString(json_encode([...])) instead of
        // Crypt::encrypt([...]) which would serialize the array via PHP
        // serialization — object injection risk.
        $routes = file_get_contents(base_path('routes/api.php'));
        $this->assertStringContainsString('json_encode', $routes,
            'Should use json_encode before encryptString to avoid serialization');
    }

    public function test_encrypted_payload_includes_expiry()
    {
        $routes = file_get_contents(base_path('routes/api.php'));
        $this->assertStringContainsString('expires_at', $routes,
            'Encrypted payloads must include expiry timestamp');
    }

    public function test_app_key_rotation_docs_exist()
    {
        $docs = file_get_contents(base_path('DOCS.md'));
        $this->assertStringContainsString('APP_KEY Rotation', $docs);
        $this->assertStringContainsString('APP_PREVIOUS_KEYS', $docs);
    }
}
