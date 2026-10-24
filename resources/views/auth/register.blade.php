<x-layouts.guest title="Create account">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="name">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="input {{ $errors->has('name') ? 'input-error' : '' }}">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="input {{ $errors->has('email') ? 'input-error' : '' }}">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="phone">Phone <span class="font-normal text-gray-400">(optional)</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"
                   class="input {{ $errors->has('phone') ? 'input-error' : '' }}">
            <p class="hint">Needed if you want to reset your password by text message.</p>
            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="password">Password</label>
                <x-password-input id="password" name="password" required :invalid="$errors->has('password')" />
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm password</label>
                <x-password-input id="password_confirmation" name="password_confirmation" required />
            </div>
        </div>
        @error('password') <p class="field-error -mt-2">{{ $message }}</p> @enderror
        <button type="submit" class="btn-primary w-full py-2.5">Create account</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-500">
        Already have an account? <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:underline">Sign in</a>
    </p>
</x-layouts.guest>
