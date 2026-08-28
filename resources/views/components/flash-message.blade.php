@if (session('success') || session('error'))
    @php($isError = session()->has('error'))
    <div @class([
        'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm',
        'border-neutral-300 bg-white text-neutral-900' => ! $isError,
        'border-neutral-400 bg-neutral-100 text-neutral-950' => $isError,
    ]) role="{{ $isError ? 'alert' : 'status' }}">
        <x-icon :name="$isError ? 'warning' : 'check'" class="mt-0.5 size-4 shrink-0" />
        <p>{{ session($isError ? 'error' : 'success') }}</p>
    </div>
@endif
