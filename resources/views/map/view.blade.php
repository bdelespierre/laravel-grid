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

        table.map td.wall {
            background-position: -112px -32px;
        }

        table.map td.gravel {
            background-position: -32px -16px;
        }

        table.map td.stone,
        table.map td.grass {
            background-position: -48px -16px;
        }

        .tile.top     { background-position: -16px  -48px }
        .tile.right   { background-position: -32px  -64px }
        .tile.bottom  { background-position: -16px  -80px }
        .tile.left    { background-position:   0px  -64px }

        table.map td.grass-1 { background-position:   0px -128px }
        table.map td.grass-2 { background-position: -16px -128px }
        table.map td.grass-3 { background-position: -64px  -16px }
    </style>
</head>
<body>
    @php
        $size = 128;

        $grass_variants = [
            'grass'   => [  0,  .8], // 80%
            'grass-1' => [ .8,  .9], // 10%
            'grass-2' => [ .9, .96], // 6%
            'grass-3' => [.96,   1], // 4%
        ];

        $directions = [
            'top'    => [0, -1], // go north
            'right'  => [+1, 0], // go east
            'bottom' => [0, +1], // go south
            'left'   => [-1, 0], // go west
        ];

        $get_tile_class = function ($x, $y) use (&$get_tile_class, $cells, $grass_variants) {
            if ($cells["{$x}:{$y}"]['tile'] == 'stone' &&
                isset($cells["{$x}:" . ($y - 1)]) &&
                $cells["{$x}:" . ($y - 1)]['tile'] == 'stone' &&
                isset($cells["{$x}:" . ($y + 1)]) &&
                $cells["{$x}:" . ($y + 1)]['tile'] != 'stone'
            ) {
                return 'wall';
            }

            if ($cells["{$x}:{$y}"]['tile'] != 'stone' &&
                isset($cells["{$x}:" . ($y - 1)]) &&
                $get_tile_class($x, $y -1) == 'stone'
            ) {
                return 'wall';
            }

            if ($cells["{$x}:{$y}"]['tile'] == 'grass') {
                $r = mt_rand() / mt_getrandmax();
                foreach ($grass_variants as $class => $p) {
                    if ($r > $p[0] && $r <= $p[1]) {
                        return $class;
                    }
                }
            }

            return $cells["{$x}:{$y}"]['tile'];
        };

        $get_tile_classes = function ($x, $y) use ($get_tile_class, $cells, $directions) {
            $classes = [$class = $get_tile_class($x, $y)];

            if ($class == 'stone' || $class == 'water') {
                foreach ($directions as $n => $dir) {
                    list($ox, $oy) = $dir;

                    $nx = $x + $ox;
                    $ny = $y + $oy;

                    if (!isset($cells["{$nx}:{$ny}"])) {
                        continue;
                    }

                    if ($get_tile_class($nx, $ny) != $get_tile_class($x, $y)) {
                        $classes[] = $n;
                    }
                }
            }

            return $classes;
        };
    @endphp
    <table class="map" width="{{ $size * 16 }}px" height="{{ $size * 16 }}px">
        @for ($j = 0; $j < ($grid->width == -1 ? ($size - 1) : $grid->width); $j++)
            <tr>
                @for ($i = 0; $i < ($grid->height == -1 ? ($size - 1) : $grid->height); $i++)
                    @php
                        $classes = $get_tile_classes($i, $j);
                    @endphp
                    <td class="{{ array_shift($classes) }}"
                        title="{{ $cells["{$i}:{$j}"]->x }}:{{ $cells["{$i}:{$j}"]->y }}">
                        @foreach ($classes as $class)
                            <div class="tile {{ $class }}"></div>
                        @endforeach
                    </td>
                @endfor
            </tr>
        @endfor
    </table>
</body>
</html>
