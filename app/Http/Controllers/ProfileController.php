<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:45',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_picture')) {
            $uploadPath = public_path('uploads/profile-pictures');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                @unlink(public_path($user->profile_picture));
            }

            $file = $request->file('profile_picture');
            $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);

            $validated['profile_picture'] = 'uploads/profile-pictures/' . $filename;
        } else {
            unset($validated['profile_picture']);
        }

        $user->update($validated);

        return redirect()
            ->route('profile')
            ->with('profile_success', 'Profile updated successfully.');
    }

    public function requestPasswordReset(Request $request)
    {
        $user = $request->user();

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()
                ->route('profile')
                ->with('password_success', 'We sent a password reset link to ' . $user->email . '.');
        }

        return redirect()
            ->route('profile')
            ->with('password_error', __($status));
    }

    public function removePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
            @unlink(public_path($user->profile_picture));
        }

        $user->update(['profile_picture' => null]);

        return redirect()
            ->route('profile')
            ->with('profile_success', 'Profile picture removed.');
    }
}
