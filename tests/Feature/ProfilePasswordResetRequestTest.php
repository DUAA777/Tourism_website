<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfilePasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_request_password_reset_from_profile(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'derptoon@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.request'));

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('password_success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
