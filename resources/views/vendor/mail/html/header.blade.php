<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                {{-- Puedes poner aquí la URL de tu logo corporativo --}}
                <img src="{{ $message->embed(public_path('images/moncobra-1l.png')) }}" class="logo" alt="Moncobra Logo" style="max-height: 75px; width: auto;"> class="logo" alt="Moncobra Logo" style="max-height: 75px; width: auto;">
            @else
                {{ $slot }}
            @endif
        </a>
    </td>
</tr>