<x-layouts.guest title="Sign in">
    <div class="space-y-5">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="input {{ $errors->has('email') ? 'input-error' : '' }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <x-password-input id="password" name="password" autocomplete="current-password" required :invalid="$errors->has('password')" />
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Keep me signed in
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:underline">Forgot password?</a>
            </div>
            <button type="submit" class="btn-primary w-full py-2.5">Sign in</button>
        </form>

        <p class="text-center text-sm text-gray-500">
            New customer? <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:underline">Create an account</a>
        </p>

    </div>
</x-layouts.guest>
