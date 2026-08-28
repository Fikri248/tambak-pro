<tr class="transition-colors hover:bg-neutral-50/70">
    @foreach ($row['cells'] as $cell)
        @php
            $data = is_array($cell) ? $cell : ['text' => (string) $cell];
            $align = $data['align'] ?? 'left';
        @endphp
        <td @class([
            'whitespace-nowrap px-5 py-3.5 text-sm text-neutral-600 first:pl-6 last:pr-6',
            'text-center tabular-nums' => $align === 'right',
            'text-center' => $align === 'center' || ($data['badge'] ?? false),
            'font-mono text-xs' => $data['mono'] ?? false,
        ])>
            @if ($data['badge'] ?? false)
                <x-badge>{{ $data['text'] }}</x-badge>
            @elseif ($data['url'] ?? null)
                <a href="{{ $data['url'] }}" class="font-medium text-neutral-900 hover:underline">{{ $data['text'] }}</a>
            @else
                {{ $data['text'] }}
            @endif
        </td>
    @endforeach
</tr>
