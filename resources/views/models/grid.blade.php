<table>
    <tbody>
        @for ($i = 0; $i < $grid->width; $i++)
            <tr>
                @for ($j = 0; $j < $grid->height; $j++)
                    <td class="{{ $grid->at($i, $j)['alive'] ? 'alive' : 'dead' }}"></td>
                @endfor
            </tr>
        @endfor
    </tbody>
</table>
