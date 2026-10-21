@php
    /** @var \App\Models\Court|null $court */
    $editing = $editing ?? isset($court);
    $action = $editing ? route('admin.courts.update', $court) : route('admin.courts.store');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      class="surface grid gap-6 p-5 sm:grid-cols-2 sm:p-8">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-slate-300">Court name</label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $editing ? $court->name : '') }}"
               class="mt-1 block w-full rounded-md border border-slate-600 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
        @error('name')
            <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="location" class="block text-sm font-medium text-slate-300">Location</label>
        <input id="location" name="location" type="text" required
               value="{{ old('location', $editing ? $court->location : '') }}"
               class="mt-1 block w-full rounded-md border border-slate-600 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
        @error('location')
            <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-slate-300">
            Description <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <textarea id="description" name="description" rows="3"
                  class="mt-1 block w-full rounded-md border border-slate-600 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">{{ old('description', $editing ? $court->description : '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
    @enderror
    </div>

    <div class="sm:col-span-2 form-section">
        <label for="image" class="block text-sm font-medium text-slate-300">Court photo</label>

        @if ($editing && $court->imageUrl())
            <img src="{{ $court->imageUrl() }}" alt="Current photo of {{ $court->name }}"
                 class="mt-2 h-40 w-full rounded-md border border-slate-700 object-cover">
        @endif

        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
               class="mt-2 block w-full text-sm text-slate-400 file:mr-4 file:rounded-md file:border-0
                      file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white
                      hover:file:bg-slate-700">
        <p class="mt-1 text-xs text-slate-400">JPG, PNG, or WEBP. At least 320x240. Maximum 4 MB.</p>
        @if ($editing && $court->imageUrl())
            <p class="mt-1 text-xs text-slate-400">Uploading a new photo replaces the current one.</p>
        @endif
        @error('image')
            <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="hourly_rate" class="block text-sm font-medium text-slate-300">Hourly rate (₱)</label>
        <input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" required
               value="{{ old('hourly_rate', $editing ? $court->hourly_rate : '') }}"
               class="mt-1 block w-full rounded-md border border-slate-600 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
        <p class="mt-1 text-xs text-slate-400">Changing this rate does not affect reservations that already booked it.</p>
        @error('hourly_rate')
            <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 self-start rounded-xl border border-slate-700 bg-slate-950/40 p-4 sm:mt-6">
        <input id="is_active" name="is_active" type="checkbox" value="1"
               @checked(old('is_active', $editing ? $court->is_active : true))
               class="rounded border-slate-600 text-slate-100 focus:ring-slate-500">
        <label for="is_active" class="text-sm text-slate-300">Active and available for booking</label>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-slate-700 pt-6 sm:col-span-2">
        <button type="submit"
                class="button button-primary">
            {{ $editing ? 'Save changes' : 'Create court' }}
        </button>
        <a href="{{ $editing ? route('admin.courts.show', $court) : route('admin.courts.index') }}"
           class="text-sm text-slate-400 hover:text-white">Cancel</a>
    </div>
</form>
