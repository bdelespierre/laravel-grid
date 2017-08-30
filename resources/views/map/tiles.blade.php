<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map Tiles</title>

    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
</head>
<body>
    @php
        $tiles = [
            "brick-wall",
            "brick-wall-2",
            "brick-wall-3",
            "brick-wall-4",
            "brick-wall-5",
            "brick-wall-6",
            "brick-wall-7",
            "brick-wall-8",
            "wood",
            "red",
            "sand",
            "grass",
            "flowers",
            "water",
            "bricks",
            "stone-wall",
            "plank",
            "carpet",
            "sand-round",
            "grass-round",
            "plan",
            "waterfall",
            "black",
            "stone-wall-2",
            "stone-edge-top-left",
            "stone-edge-top",
            "stone-edge-top-right",
            "pot",
            "pot-broken",
            "glass",
            "tree",
            "well",
            "stone-edge-left",
            "entrance-south",
            "stone-edge-right",
            "chest-open",
            "chest-close",
            "glass-2",
            "tree-2",
            "column",
            "stone-edge-bottom-left",
            "stone-edge-bottom",
            "stone-edge-bottom-right",
            "castle",
            "village",
            "bed-top",
            "throne",
            "statue",
            "wood-door",
            "iron-door",
            "entrance-top",
            "posts",
            "map",
            "bed-bottom",
            "table",
            "drawer",
            "stairs-up",
            "stairs-down",
            "rocks",
            "posts-2",
            "torch",
            "torch-2",
            "mountain",
            "mountain-2",
            "grass-2",
            "grass-3",
            "lava-stone-wall",
            "post",
            "gold",
            "gems",
            "post-armor",
            "post-magic",
            "wood-2",
            "bricks-2",
            "lava",
            "lava-2",
            "tree-3",
            "tree-4",
            "post-sword",
            "post-potion",
            "stone-bridge-vert",
            "stone-bridge-horz",
            "wood-bridge-vert",
            "wood-bridge-horz",
            "column-top",
            "tree-5",
            "dialog-box-top-left",
            "dialog-box-top",
            "dialog-box-top-right",
            "column-bottom",
            "dialog-box-left",
            "dialog-box-background",
            "dialog-box-right",
            "tree-6",
            "tree-7",
            "dialog-box-bottom-left",
            "dialog-box-bottom",
            "dialog-box-bottom-right",
            "tree-8",
            "tree-9",
        ];
    @endphp

    <div class="map">
        <div class="row" style="width: {{ 8*16 }}px">
            @foreach ($tiles as $i => $class)
                <div class="cell">
                    <div class="tile {{ $class }}" title="{{ $class }}"></div>
                </div>

                @if (($i+1) % 8 == 0)
                    </div><div class="row" style="width: {{ 8*16 }}px">
                @endif
            @endforeach
        </div>
    </div>

</body>