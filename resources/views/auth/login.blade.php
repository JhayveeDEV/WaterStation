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
            <div x-data="{ passwordVisible: false }">
                <label class="label" for="password">Password</label>
                <div class="relative">
                    <input id="password" :type="passwordVisible ? 'text' : 'password'" name="password" required autocomplete="current-password"
                           class="input pr-12 {{ $errors->has('password') ? 'input-error' : '' }}">
                    <button type="button" @click="passwordVisible = !passwordVisible"
                            :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
                            :title="passwordVisible ? 'Hide password' : 'Show password'"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500"
                            aria-controls="password">
                        <svg x-show="!passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.5 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.5 10.5 0 0 0 2.25 12s3.5 7.5 9.75 7.5a10.4 10.4 0 0 0 5.02-1.28M6.23 6.23A10.4 10.4 0 0 1 12 4.5c6.25 0 9.75 7.5 9.75 7.5a10.6 10.6 0 0 1-2.47 3.53M6.23 6.23 3 3m3.23 3.23 4.15 4.15m7.37 7.37L21 21m-3.25-3.25-4.15-4.15m0 0a2.25 2.25 0 0 1-3.18-3.18" />
                        </svg>
                    </button>
                </div>
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
