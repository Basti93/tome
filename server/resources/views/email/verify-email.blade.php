<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>E-Mail-Adresse verifizieren</title>
</head>
<body>
    <h1>E-Mail-Adresse verifizieren</h1>
    <br />
    <h2>Hallo {{$user->firstName}}</h2>
    <p>Danke für deine Registrierung! Um dein Konto zu aktivieren, muss du deine E-Mail-Adresse verifizieren.</p>
    <br />
    <p><a href="{{$verificationLink}}">Klick hier, um deine E-Mail zu verifizieren</a></p>
    <br />
    <p>Dieser Link läuft in 24 Stunden ab.</p>
    <br />
    <p>Falls du diesen Link nicht angefordert hast, ignoriere diese E-Mail.</p>
</body>
</html>
