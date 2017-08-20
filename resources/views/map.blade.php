<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map Test</title>
    <style>
        table td.alive,
        table td.dead {
            width: 10px;
            height: 10px;
            margin: 0;
            padding: 0;
        }

        td.dead {
            background: #333;
        }

        .grid:after {
            content: "";
            display: block;
            clear: both;
        }

        .cell {
            width: 10px;
            height: 10px;
            float: left;
            margin-right: 1px;
            margin-bottom: 1px;
        }

        .cell.dead {
            background: #333;
        }
    </style>
</head>
<body>
    <div class="grid" style="width: 440px">
        @foreach ($cells as $cell)
            <div class="cell {{ $cell['alive'] ? 'alive' : 'dead' }}" title="{{ $cell->x }} {{ $cell->y }}"></div>
        @endforeach
    </div>

    <table>
        <tbody>
            @for ($i = 0; $i < 40; $i++)
                <tr>
                    @for ($j = 0; $j < 40; $j++)
                        <td class="{{ $grid->at($i, $j)['alive'] ? 'alive' : 'dead' }}" title="{{ $grid->at($i,$j)->x }} {{ $grid->at($i,$j)->y }}"></td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
