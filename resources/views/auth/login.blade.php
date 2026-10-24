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
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="input {{ $errors->has('password') ? 'input-error' : '' }}">
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

        <div class="border-t border-slate-100 pt-4">
            <p class="mb-3 text-center text-xs font-medium text-slate-500">Or try a demo account</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach (['admin', 'staff', 'rider', 'customer'] as $demo)
                    <button type="button" data-demo="{{ $demo }}@waterstation.test"
                            class="btn-secondary justify-start rounded-xl px-3 py-2 capitalize">
                        <span class="avatar h-5 w-5 text-[10px]">{{ strtoupper(substr($demo, 0, 1)) }}</span>{{ $demo }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-demo]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('email').value = btn.dataset.demo;
                document.getElementById('password').value = 'password';
                document.getElementById('password').focus();
            });
        });
    </script>
</x-layouts.guest>
