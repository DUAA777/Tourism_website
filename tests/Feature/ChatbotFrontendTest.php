<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_chatbot_page(): void
    {
        $response = $this->get(route('chatbot'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_the_chatbot_frontend_shell(): void
    {
        $user = User::factory()->create([
            'name' => 'Mira Salem',
        ]);

        $response = $this->actingAs($user)->get(route('chatbot'));

        $response->assertOk()
            ->assertSee('Plan a trip, not just a prompt.')
            ->assertSee('Start with one of these')
            ->assertSee('Get better answers faster')
            ->assertSee('Ask Yalla Nemshi')
            ->assertSee('New Chat')
            ->assertSee('2 day seaside trip in Batroun')
            ->assertSee('Romantic dinner in Beirut')
            ->assertSee(route('chatbot.send'), false)
            ->assertSee(route('chatbot.newSession'), false)
            ->assertSee('assets/css/chatbot-fullscreen.css', false)
            ->assertSee('assets/js/chatbot.js', false)
            ->assertSee('Mira Salem')
            ->assertSee('User')
            ->assertSee('Logout');
    }

    public function test_admin_user_sees_admin_shortcuts_in_account_dropdown(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('chatbot'));

        $response->assertOk()
            ->assertSee('Admin')
            ->assertSee('Admin dashboard')
            ->assertDontSee('Manage users')
            ->assertDontSee('Manage restaurants')
            ->assertDontSee('Manage hotels');
    }
}
