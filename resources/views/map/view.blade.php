<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map {{ $grid->name }}</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="map">
        @for ($y = 0; $y < $size; $y++)
            <div class="row" style="width: {{ $size * 16 }}px">
                @for ($x = 0; $x < $size; $x++)
                    @php
                        $cell = $cells["{$x}:{$y}"] ?? null;
                    @endphp
                    @if ($cell)
                        <div id="{{ $cell->id }}"
                            class="cell"
                            title="{{ implode(':', $cell->coordinates) }}"
                            data-terrain-elevation="{{ $cell['terrain.elevation'] }}"
                            data-x="{{ $cell->x }}"
                            data-y="{{ $cell->y }}">
                        </div>
                    @else
                        <div
                            class="tile"
                            data-terrain-elevation="grass"
                            data-x="{{ $x }}"
                            data-y="{{ $y }}"></div>
                    @endif
                @endfor
            </div>
        @endfor
    </div>

    <script
        src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g="
        crossorigin="anonymous"></script>
    <script type="text/javascript">
        var grass_variants = {
            'grass'   : [  0,  .8], // 80%
            'grass-2' : [ .8,  .9], // 10%
            'grass-3' : [ .9, .96], //  6%
            'flowers' : [.96,   1], //  4%
        };

        // because it's easier
        var directions = {
            // +
            'top'         : [ 0, -1],
            'left'        : [-1,  0],
            'right'       : [+1,  0],
            'bottom'      : [ 0, +1],

            // x
            'top-left'    : [-1, -1],
            'top-right'   : [+1, -1],
            'bottom-left' : [-1, +1],
            'bottom-right': [+1, +1]
        };

        var map = {
            cells: {},

            has: function(x, y) {
                return this.get(x, y) !== undefined;
            },

            get: function(x, y) {
                return this.cells[x + ':' + y];
            },

            add: function(x, y, cell) {
                this.cells[x + ':' + y] = cell;
                return this;
            },

            each: function(fn) {
                $.each(this.cells, function(i, cell) {
                    fn.call(cell, cell, i, this.cells)
                });
                return this;
            },

            update: function() {
                this.each(function(cell) {
                    $(cell.node).html('');
                    cell.layers.forEach(function(layer) {
                        $(cell.node).append('<div class="tile ' + layer + '"></div>');
                    });
                })
            }
        };

        $(function() {
            $('.map .cell').each(function(i, item) {
                var $item = $(item),
                    x = parseInt($item.attr('data-x')),
                    y = parseInt($item.attr('data-y')),
                    type = $item.attr('data-terrain-elevation');

                map.add(x, y, {
                    id: $item.attr('id'),
                    node: item,
                    x: x,
                    y: y,
                    type: type,
                    solid: type == 'stone' || type == 'water',
                    layers: [],

                    eachNeighbor: function (fn, constraint) {
                        var that = this;
                        $.each(directions, function(name, dir) {
                            if (constraint == '+') {
                                if (name == 'top-left'    || name == 'top-right' ||
                                    name == 'bottom-left' || name == 'bottom-right') {
                                    return;
                                }
                            } else if (constraint == 'x') {
                                if (name == 'top'    || name == 'right' ||
                                    name == 'bottom' || name == 'left') {
                                    return;
                                }
                            }

                            if (that.hasNeighbor(name)) {
                                let cell = that.getNeighbor(name);
                                return fn.call(cell, cell, name, map.cells);
                            }
                        });
                    },

                    getNeighbor: function(dir) {
                        let x = this.x + directions[dir][0],
                            y = this.y + directions[dir][1];

                        return map.get(x, y);
                    },

                    hasNeighbor: function(dir) {
                        return this.getNeighbor(dir) !== undefined;
                    }
                });
            });

            // elevation
            map.each(function(cell) {
                if (cell.type == 'stone' && cell.hasNeighbor('top') && cell.getNeighbor('top').type != 'stone') {
                    cell.getNeighbor('top').type = 'stone';
                }
            });

            // tiling
            map.each(function(cell) {
                if (cell.type == 'stone') {
                    if (cell.hasNeighbor('bottom') && cell.getNeighbor('bottom').type != 'stone') {
                        cell.type = 'stone-wall';
                        cell.layers.push('stone-wall');
                    } else {
                        cell.layers.push('grass');
                    }
                } else if (cell.type == 'grass') {
                    let rand = Math.random(); // [0,1]
                    $.each(grass_variants, function(name, prob) {
                        if (rand >= prob[0] && rand < prob[1]) {
                            cell.layers.push(name);
                        }
                    });
                } else if (cell.type == 'gravel') {
                    cell.layers.push('sand');
                } else if (cell.type == 'water') {
                    cell.layers.push('water');
                }
            });

            // hard edges
            map.each(function(cell) {
                if (cell.type == 'stone' || cell.type == 'water') {
                    cell.eachNeighbor(function(adjacent, dir) {
                        if (cell.type != adjacent.type) {
                            cell.layers.push('stone-edge-' + dir);
                        }
                    }, '+');
                }
            });

            map.update();
        });
    </script>
</body>
</html>
