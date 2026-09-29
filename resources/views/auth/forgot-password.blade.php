<x-layouts.guest title="Forgot password">
    <p class="mb-5 text-sm leading-relaxed text-gray-500">
        Enter your account email. If it has a phone number on file, we'll text a 6-digit code to it.
    </p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="input {{ $errors->has('email') ? 'input-error' : '' }}">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary w-full py-2.5">Send reset code</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:underline">Back to sign in</a>
    </p>
</x-layouts.guest>
