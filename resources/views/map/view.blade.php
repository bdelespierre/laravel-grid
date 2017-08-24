<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map {{ $grid->name }}</title>
    <style>
        table.map {
            border-collapse: collapse;
        }

        table.map td {
            width: 10px;
            height: 10px;
            margin: 0;
            padding: 0;
        }

        table.map td.stone {
            background: black;
        }

        table.map td.water,
        table.map td.river {
            background: blue;
        }

        table.map td.dirt {
            background: orange;
        }

        table.map td.gravel {
            background: grey;
        }

        table.map td.grass {
            background: green;
        }
    </style>
</head>
<body>
    <table class="map">
        @for ($i = 0; $i < ($grid->width == -1 ? 127 : $grid->width); $i++)
            <tr>
                @for ($j = 0; $j < ($grid->height == -1 ? 127 : $grid->height); $j++)
                    <td class="{{ $cells["{$i}:{$j}"]['tile'] }}" title="{{ $cells["{$i}:{$j}"]->x }}:{{ $cells["{$i}:{$j}"]->y }}"></td>
                @endfor
            </tr>
        @endfor
    </table>
</body>
</html>
