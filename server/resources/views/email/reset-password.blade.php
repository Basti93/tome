<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort zurücksetzen</title>
</head>
<body>
    <h1>Passwort zurücksetzen</h1>
    <br />
    <h2>Hallo {{$user->firstName}}</h2>
    <p>Du erhältst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen deines Passworts für dein Konto erhalten haben.</p>
    <br />
    <p><a href="{{$resetLink}}">Klick hier, um dein Passwort zurückzusetzen</a></p>
    <br />
    <p>Dieser Link läuft in 60 Minuten ab.</p>
    <br />
    <p>Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail.</p>
</body>
</html>
