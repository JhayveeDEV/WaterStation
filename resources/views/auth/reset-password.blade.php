<x-layouts.guest title="Reset password">
    <p class="mb-5 text-sm leading-relaxed text-gray-500">
        Enter the 6-digit code we texted you, then choose a new password.
    </p>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="code">6-digit code</label>
            <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus
                   class="input py-3 text-center text-xl font-semibold tracking-[0.5em] {{ $errors->has('code') ? 'input-error' : '' }}">
            @error('code') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="input {{ $errors->has('password') ? 'input-error' : '' }}">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input">
        </div>
        <button type="submit" class="btn-primary w-full py-2.5">Reset password</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-500">
        Didn't get a code? <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:underline">Send a new one</a>
    </p>
</x-layouts.guest>
