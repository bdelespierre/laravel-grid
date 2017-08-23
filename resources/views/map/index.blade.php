<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maps</title>
</head>
<body>
    <ul>
        @foreach ($grids as $grid)
            <li><a href="/map/{{ $grid->id }}">{{ $grid->name }}</a></li>
        @endforeach
    </ul>
</body>
</html>
