<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map Test</title>
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

        table.map td.water {
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
        @for ($i = 0; $i < 100; $i++)
            <tr>
                @for ($j = 0; $j < 100; $j++)
                    <td class="{{ $cells["{$i}:{$j}"]['tile'] }}" title="{{ $cells["{$i}:{$j}"]['avg'] }}"></td>
                @endfor
            </tr>
        @endfor
    </table>
</body>
</html>
