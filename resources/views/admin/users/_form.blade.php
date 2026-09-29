<div>
    <label class="label" for="name">Full name</label>
    <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
    @error('name') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="input {{ $errors->has('email') ? 'input-error' : '' }}" required>
    @error('email') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="phone">Phone</label>
    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone ?? '') }}" class="input {{ $errors->has('phone') ? 'input-error' : '' }}">
    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="role">Role</label>
        <select id="role" name="role" class="input" required>
            @foreach (['admin', 'staff', 'customer', 'rider'] as $r)
                <option value="{{ $r }}" {{ old('role', $user->roles->first()?->name ?? '') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
        @error('role') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="status">Status</label>
        <select id="status" name="status" class="input" required>
            <option value="active" {{ old('status', $user->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $user->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
<div>
    <label class="label" for="password">Password</label>
    <input id="password" type="password" name="password" autocomplete="new-password" class="input {{ $errors->has('password') ? 'input-error' : '' }}" {{ isset($user) ? '' : 'required' }}>
    @if (isset($user)) <p class="hint">Leave blank to keep the current password.</p> @endif
    @error('password') <p class="field-error">{{ $message }}</p> @enderror
</div>
