<x-layouts.app title="My account">
    <div class="mx-auto max-w-xl space-y-6">
        <div class="flex items-center gap-4">
            <span class="avatar h-12 w-12 text-base">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div>
                <p class="text-base font-semibold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500"><span class="capitalize">{{ $user->getRoleNameAttribute() }}</span> account</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Profile information</h3></div>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 p-5">
                @csrf @method('PUT')
                <div>
                    <label class="label" for="profile-name">Full name</label>
                    <input id="profile-name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name"
                           class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="profile-email">Email</label>
                    <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email"
                           class="input {{ $errors->has('email') ? 'input-error' : '' }}" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="profile-phone">Phone</label>
                    <input id="profile-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                           class="input {{ $errors->has('phone') ? 'input-error' : '' }}">
                    <p class="hint">We text your password reset code to this number.</p>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="btn-primary">Save changes</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Change password</h3></div>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 p-5">
                @csrf @method('PUT')
                <div>
                    <label class="label" for="current_password">Current password</label>
                    <x-password-input id="current_password" name="current_password" autocomplete="current-password" required :invalid="$errors->has('current_password')" />
                    @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="new_password">New password</label>
                    <x-password-input id="new_password" name="password" required :invalid="$errors->has('password')" />
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">Confirm new password</label>
                    <x-password-input id="password_confirmation" name="password_confirmation" required />
                </div>
                <button class="btn-primary">Update password</button>
            </form>
        </div>
    </div>
</x-layouts.app>
