<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map {{ $grid->name }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }

        table.map {
            border-spacing: 0px;
            border-collapse: collapse;
        }

        table.map td, .tile {
            width: 16px;
            height: 16px;
            min-width: 16px;
            min-height: 16px;
            max-width: 16px;
            max-height: 16px;
            margin: 0;
            padding: 0;
            background-image: url('/images/tiles/16x16/basic.png');
            background-repeat: no-repeat;
            border: none;
        }

        table.map td {
            position: relative;
        }

        .tile {
            position: absolute;
            top: 0;
            left: 0;
        }

        table.map td.water,
        table.map td.river {
            background-position: -80px -16px;
        }

        table.map td.stone {
            background-position: -112px -32px;
        }

        table.map td.gravel {
            background-position: -32px -16px;
        }

        table.map td.grass {
            background-position: -48px -16px;
        }

        .tile.top    { background-position: -16px -48px }
        .tile.right  { background-position: -32px -64px }
        .tile.bottom { background-position: -16px -80px }
        .tile.left   { background-position:   0px -64px }
    </style>
</head>
<body>
    @php
        $size = 128;

        $directions = [
            'top'    => [0, -1], // go north
            'right'  => [+1, 0], // go east
            'bottom' => [0, +1], // go south
            'left'   => [-1, 0], // go west
        ];

        $get_tiles = function ($x, $y) use ($cells, $directions) {
            $c = [];
            foreach ($directions as $n => $dir) {
                list($ox, $oy) = $dir;
                $nx = $x + $ox;
                $ny = $y + $oy;
                if (!isset($cells["{$nx}:{$ny}"])) {
                    continue;
                }

                if ($cells["{$nx}:{$ny}"]['tile'] != $cells["{$x}:{$y}"]['tile']) {
                    $c[] = $n;
                }
            }

            return $c;
        };
    @endphp
    <table class="map" width="{{ $size * 16 }}px" height="{{ $size * 16 }}px">
        @for ($j = 0; $j < ($grid->width == -1 ? ($size - 1) : $grid->width); $j++)
            <tr>
                @for ($i = 0; $i < ($grid->height == -1 ? ($size - 1) : $grid->height); $i++)
                    <td class="{{ $cells["{$i}:{$j}"]['tile'] }}" title="{{ $cells["{$i}:{$j}"]->x }}:{{ $cells["{$i}:{$j}"]->y }}">
                        @if ($cells["{$i}:{$j}"]['tile'] == 'stone' || $cells["{$i}:{$j}"]['tile'] == 'water')
                            @foreach ($get_tiles($i,$j) as $class)
                                <div class="tile {{ $class }}"></div>
                            @endforeach
                        @endif
                    </td>
                @endfor
            </tr>
        @endfor
    </table>
</body>
</html>
