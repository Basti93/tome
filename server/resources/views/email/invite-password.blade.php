<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Einladung zu T.O.M.E.</title>
</head>
<body>
    <h1>Einladung zu T.O.M.E.</h1>
    <br />
    <h2>Hallo {{$user->firstName}}</h2>
    <p>Für dich wurde ein Konto in der Trainingsverwaltung T.O.M.E. angelegt. Lege jetzt dein Passwort fest, um dich anzumelden.</p>
    <br />
    <p><a href="{{$resetLink}}">Klick hier, um dein Passwort festzulegen</a></p>
    <br />
    <p>Dieser Link läuft in {{ config('auth.passwords.users.expire', 60) }} Minuten ab. Danach kannst du über "Passwort vergessen" auf der Anmeldeseite einen neuen Link anfordern.</p>
    <br />
    <p>Falls du diese Einladung nicht erwartet hast, ignoriere diese E-Mail.</p>
</body>
</html>
