<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserShippingAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ShippingAddressTest extends TestCase
{
    public function test_shipping_addresses_endpoints_are_protected()
    {
        $response = $this->getJson('/api/v1/shipping-addresses');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/shipping-addresses', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/shipping-addresses/1');
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/shipping-addresses/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/v1/shipping-addresses/1');
        $response->assertStatus(401);
    }

    public function test_can_create_shipping_address()
    {
        // Use a random email to avoid duplication errors on repeated runs without cleanup
        $email = 'test_' . uniqid() . '@example.com';

        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => bcrypt('password'),
            'contact_number' => '9800000000'
        ]);
        Sanctum::actingAs($user);

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'contact_number' => '9800000000',
            'landmark' => 'Near Temple',
            'city' => 'Kathmandu',
            'district' => 'Kathmandu',
            'province' => 'Bagmati',
            'country' => 'Nepal',
            'is_default' => true
        ];

        $response = $this->postJson('/api/v1/shipping-addresses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'John');

        $this->assertDatabaseHas('user_shipping_addresses', [
            'first_name' => 'John',
            'user_id' => $user->id
        ]);

        // Cleanup
        UserShippingAddress::where('user_id', $user->id)->delete();
        $user->delete();
    }

    public function test_can_list_shipping_addresses()
    {
        $email = 'test2_' . uniqid() . '@example.com';

        $user = User::create([
            'name' => 'Test User 2',
            'email' => $email,
            'password' => bcrypt('password'),
            'contact_number' => '9800000001'
        ]);
        Sanctum::actingAs($user);

        UserShippingAddress::create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'contact_number' => '9800000000',
            'landmark' => 'Near Temple',
            'city' => 'Kathmandu',
            'district' => 'Kathmandu',
            'province' => 'Bagmati',
            'country' => 'Nepal',
            'is_default' => true
        ]);

        $response = $this->getJson('/api/v1/shipping-addresses');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Cleanup
        UserShippingAddress::where('user_id', $user->id)->delete();
        $user->delete();
    }
}
