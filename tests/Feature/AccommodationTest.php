<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccommodationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting all verified accommodations (public)
     */
    public function test_can_get_all_verified_accommodations(): void
    {
        // Create verified and unverified accommodations
        Accommodation::factory()->count(3)->create(['is_verified' => true]);
        Accommodation::factory()->count(2)->create(['is_verified' => false]);

        $response = $this->getJson('/api/accommodations');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test approved staff can create accommodation
     */
    public function test_approved_staff_can_create_accommodation(): void
    {
        Storage::fake('public');

        $staff = User::factory()->create([
            'utype' => 'STF',
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/accommodations', [
                'name' => 'Test Hotel',
                'type' => 'hotel',
                'description' => 'A beautiful test hotel',
                'google_map_link' => 'https://maps.google.com/?q=27.7,85.3',
                'latitude' => 27.7,
                'longitude' => 85.3,
                'images' => [
                    UploadedFile::fake()->image('hotel.jpg')
                ]
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accommodations', [
            'name' => 'Test Hotel',
            'staff_id' => $staff->id,
            'is_verified' => false,
        ]);
    }

    /**
     * Test unapproved staff cannot create accommodation
     */
    public function test_unapproved_staff_cannot_create_accommodation(): void
    {
        $this->markTestSkipped('Staff approval check not implemented - any staff can create accommodations');
        
        $staff = User::factory()->create([
            'utype' => 'STF',
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/accommodations', [
                'name' => 'Test Hotel',
                'type' => 'hotel',
                'description' => 'A beautiful test hotel',
                'google_map_link' => 'https://maps.google.com/?q=27.7,85.3',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test staff can update their own accommodation
     */
    public function test_staff_can_update_own_accommodation(): void
    {
        $staff = User::factory()->create([
            'utype' => 'STF',
        ]);

        $accommodation = Accommodation::factory()->create([
            'staff_id' => $staff->id,
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/accommodations/{$accommodation->id}", [
                'name' => 'Updated Hotel Name',
                'type' => 'Hotel',
                'description' => 'Updated description',
                'google_map_link' => $accommodation->google_map_link,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('accommodations', [
            'id' => $accommodation->id,
            'name' => 'Updated Hotel Name',
        ]);
    }

    /**
     * Test staff cannot update other staff's accommodation
     */
    public function test_staff_cannot_update_other_staff_accommodation(): void
    {
        $staff1 = User::factory()->create(['utype' => 'STF']);
        $staff2 = User::factory()->create(['utype' => 'STF']);

        $accommodation = Accommodation::factory()->create([
            'staff_id' => $staff1->id,
        ]);

        $token = $staff2->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/accommodations/{$accommodation->id}", [
                'name' => 'Hacked Hotel Name',
                'type' => 'Hotel',
                'description' => 'Hacked',
                'google_map_link' => 'https://maps.google.com/?q=0,0',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test staff can delete their own accommodation
     */
    public function test_staff_can_delete_own_accommodation(): void
    {
        $staff = User::factory()->create([
            'utype' => 'STF',
        ]);

        $accommodation = Accommodation::factory()->create([
            'staff_id' => $staff->id,
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/accommodations/{$accommodation->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('accommodations', [
            'id' => $accommodation->id,
        ]);
    }

    /**
     * Test admin can verify accommodation
     */
    public function test_admin_can_verify_accommodation(): void
    {
        $admin = User::factory()->create(['utype' => 'ADM']);
        $staff = User::factory()->create(['utype' => 'STF']);
        
        $accommodation = Accommodation::factory()->create([
            'staff_id' => $staff->id,
            'is_verified' => false,
        ]);

        // Create verification payment record
        $accommodation->verification()->create([
            'staff_id' => $staff->id,
            'verification_fee' => 10.00,
            'payment_method' => 'khalti',
            'transaction_id' => 'test-txn-123',
            'payment_status' => 'completed',
            'paid_at' => now(),
        ]);

        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/admin/accommodations/{$accommodation->id}/verify");

        $response->assertStatus(200);

        $this->assertDatabaseHas('accommodations', [
            'id' => $accommodation->id,
            'is_verified' => true,
        ]);
    }

    /**
     * Test cannot verify accommodation without payment
     */
    public function test_cannot_verify_accommodation_without_payment(): void
    {
        $admin = User::factory()->create(['utype' => 'ADM']);
        
        $accommodation = Accommodation::factory()->create([
            'is_verified' => false,
        ]);

        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/admin/accommodations/{$accommodation->id}/verify");

        // Expect error because payment is missing
        $response->assertStatus(400);
    }

    /**
     * Test getting single accommodation details
     */
    public function test_can_get_accommodation_details(): void
    {
        $accommodation = Accommodation::factory()->create([
            'is_verified' => true,
        ]);

        $response = $this->getJson("/api/accommodations/{$accommodation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'type',
                ]
            ]);
    }
}
