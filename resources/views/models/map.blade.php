<table>
    <tbody>
        @for ($i = 0; $i < $map->width; $i++)
            <tr>
                @for ($j = 0; $j < $map->height; $j++)
                    <td class="{{ $map->at($i, $j)['alive'] ? 'alive' : 'dead' }}"></td>
                @endfor
            </tr>
        @endfor
    </tbody>
</table>
