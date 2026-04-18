<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_change_email_from_profile_update(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '11111111',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'changed@example.com',
            'phone' => '22222222',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('profile_success');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('22222222', $user->phone);
        $this->assertSame('original@example.com', $user->email);
    }
}
