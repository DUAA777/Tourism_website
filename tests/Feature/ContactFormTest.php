<?php

namespace Tests\Feature;

use App\Mail\ContactFormMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_contact_form_sends_email_and_redirects_back(): void
    {
        Mail::fake();

        $response = $this->post(route('contactUs.send'), [
            'name' => 'Mohammad Test',
            'email' => 'mohammad@example.com',
            'subject' => 'Bug report',
            'message' => 'The chatbot page is useful, but I found a small layout issue.',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactFormMessage::class, function (ContactFormMessage $mail) {
            return $mail->contactData['email'] === 'mohammad@example.com'
                && $mail->contactData['subject'] === 'Bug report';
        });
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Mail::fake();

        $response = $this->from(route('contactUs'))->post(route('contactUs.send'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => 'short',
        ]);

        $response
            ->assertRedirect(route('contactUs'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }
}
