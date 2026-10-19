<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Training noch nicht abgeschlossen</title>
</head>
<body>
    <h2>Hallo {{$user->firstName}}</h2>
    <p>Diese Trainings sind noch nicht abgeschlossen:</p>
    <ul>
        @foreach($items as $item)
            <li><a href="{{$item['url']}}">{{$item['label']}}</a></li>
        @endforeach
    </ul>
    <p>Bitte kurz Anwesenheit und Zeiten bestätigen.</p>
</body>
</html>
