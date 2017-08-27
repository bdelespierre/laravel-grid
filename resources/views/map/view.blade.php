@php $size = 128 @endphp
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

        .map .row {
            width: {{ ($size -1) * 16 }}px;
            overflow: auto;
            zoom: 1;
        }

        .map .row > .tile {
            position: relative;
            float: left;
        }

        .tile {
            position: absolute;
            top: 0;
            left: 0;
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

        .tile.water             { background-position:  -80px -16px }
        .tile.river             { background-position:  -80px -16px }
        .tile.stone-wall        { background-position: -112px -32px }
        .tile.gravel            { background-position:  -32px -16px }
        .tile.stone             { background-position:  -48px -16px }
        .tile.grass             { background-position:  -48px -16px }
        .tile.stone-edge-top    { background-position: -16px  -48px }
        .tile.stone-edge-right  { background-position: -32px  -64px }
        .tile.stone-edge-bottom { background-position: -16px  -80px }
        .tile.stone-edge-left   { background-position:   0px  -64px }
        .tile.grass-1           { background-position:   0px -128px }
        .tile.grass-2           { background-position: -16px -128px }
        .tile.grass-3           { background-position: -64px  -16px }
    </style>
</head>
<body>
    <div class="map">
        @for ($j = 0; $j < ($grid->width == -1 ? ($size - 1) : $grid->width); $j++)
            <div class="row">
                @for ($i = 0; $i < ($grid->height == -1 ? ($size - 1) : $grid->height); $i++)
                    @php $layers = $cells["{$i}:{$j}"]['terrain.layers'] ?: ['grass'] @endphp
                    <div class="tile {{ array_shift($layers) }}"
                        title="{{ $cells["{$i}:{$j}"]->x }}:{{ $cells["{$i}:{$j}"]->y }}">
                        @foreach ($layers as $class)
                            <div class="tile {{ $class }}"></div>
                        @endforeach
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</body>
</html>
