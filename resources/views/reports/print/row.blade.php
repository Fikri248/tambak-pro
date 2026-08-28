<tr>
    @foreach ($row['cells'] as $cell)
        @php
            $data = is_array($cell) ? $cell : ['text' => (string) $cell];
            $align = $data['align'] ?? 'left';
        @endphp
        <td class="{{ $align === 'right' ? 'align-right' : ($align === 'center' ? 'align-center' : '') }}">{{ $data['text'] }}</td>
    @endforeach
</tr>
