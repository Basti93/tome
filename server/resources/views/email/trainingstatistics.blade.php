<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Monatliche Trainingsstatistik</title>
    <style>
        .table {
            border-collapse: collapse;
            min-width: 300px;
        }
        .table-border {
            border: 1px solid black;
            padding: 2px;
        }
        .center {
            text-align: center;
        }
        .left {
            text-align: left;
        }
    </style>
</head>
<body>
    <h3>Hallo Super-Trainer/in {{$user->name}}</h3>
    <p>Die monatliche Trainingsstatistik wurde gerade frisch gedruckt.</p>
    <h4>Teilnahmen am Training von {{ date_format($fromDate, 'd.m.Y') }} bis {{ date_format($untilDate, 'd.m.Y') }}</h4>
    <p style="color: red; font-style: italic;">Damit diese Statistik korrekt ist müssen die Trainings immer unter "Nachbereiten" abgeschlossen werden!</p>
    @foreach ($userGroupAttendance as $groupKey => $groupVal)
        <h5>Gruppe: {{ $groupKey  }}</h5>
        <table class="table table-border">
            <tr>
                <th class="table-border left">Name</th>
                <th class="table-border">Teilnahmen</th>
            </tr>
        @foreach ($groupVal as $userKey => $userVal)
            <tr>
                <td class="table-border">{{  $userKey  }}</td>
                <td class="table-border center">{{  $userVal  }}</td>
            </tr>
        @endforeach
        </table>
        <br>
    @endforeach
</body>
</html>
