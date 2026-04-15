<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function index()
    {
        return view('contactUs_mail');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $recipient = config('mail.contact.address', config('mail.from.address'));

        if (!$recipient) {
            return back()
                ->withInput()
                ->with('contact_error', 'Contact email is not configured yet.');
        }

        try {
            Mail::to($recipient)->send(new ContactFormMessage($validated));
        } catch (Throwable $exception) {
            report($exception);

            $message = app()->environment('local')
                ? 'Mail error: ' . $exception->getMessage()
                : 'We could not send your message right now. Please try again in a moment.';

            return back()
                ->withInput()
                ->with('contact_error', $message);
        }

        return back()->with('contact_success', 'Your message was sent successfully. We will get back to you soon.');
    }
}
