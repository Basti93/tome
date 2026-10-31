@php
    $th = 'padding:4px 3px;border-bottom:2px solid #444444;font-size:11px;line-height:13px;color:#333333;font-weight:bold;';
    $td = 'padding:4px 3px;border-bottom:1px solid #dddddd;font-size:13px;line-height:16px;color:#222222;';
    $h = 'margin:18px 0 6px 0;font-size:15px;color:#222222;';
    $pct = fn ($v) => $v === null ? '–' : $v.' %';
    $trendText = fn ($t) => $t === null ? '–' : ($t > 0 ? '▲' : ($t < 0 ? '▼' : '='));
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monatliche Trainingsstatistik</title>
</head>
<body style="margin:0;padding:8px;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#222222;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;">
<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;">
    <p style="margin:0 0 8px 0;font-size:16px;"><strong>Hallo Super-Trainer/in {{ $report['trainerName'] }}</strong></p>
    <p style="margin:0 0 8px 0;">Die monatliche Trainingsstatistik wurde gerade frisch gedruckt: Teilnahmen am Training von {{ $report['from'] }} bis {{ $report['until'] }}.</p>
    <p style="margin:0 0 8px 0;color:#c00000;font-style:italic;">Damit die Statistik stimmt, müssen Trainings immer unter "Nachbereiten" abgeschlossen und die Sportlerinnen und Sportler richtig ein- und ausgetragen werden.</p>

    @if ($report['mine'])
        <h3 style="{{ $h }}">Deine Einheiten</h3>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr>
                <td style="{{ $td }}">Im Monat</td>
                <td style="{{ $td }}text-align:right;"><strong>{{ $report['mine']['monthCount'] }}</strong> Einheiten, {{ number_format($report['mine']['monthHours'], 2, ',', '.') }} Std.</td>
            </tr>
            <tr>
                <td style="{{ $td }}">Im Jahr {{ $report['year'] }}</td>
                <td style="{{ $td }}text-align:right;"><strong>{{ $report['mine']['yearCount'] }}</strong> Einheiten, {{ number_format($report['mine']['yearHours'], 2, ',', '.') }} Std.</td>
            </tr>
        </table>
    @endif

    @foreach ($report['groups'] as $group)
        @php $s = $group['summary']; @endphp
        <h3 style="margin:26px 0 6px 0;font-size:17px;color:#222222;border-top:2px solid #444444;padding-top:10px;">Gruppe: {{ $group['name'] }}</h3>

        @if ($s['notEvaluated'] > 0)
            <p style="margin:0 0 8px 0;padding:6px 8px;border:2px solid #c00000;color:#c00000;font-weight:bold;">
                Achtung: {{ $s['notEvaluated'] }} {{ $s['notEvaluated'] === 1 ? 'Training' : 'Trainings' }} in diesem Monat {{ $s['notEvaluated'] === 1 ? 'wurde' : 'wurden' }} noch nicht nachbereitet. Die Zahlen sind unvollständig.
            </p>
        @endif

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr><td style="{{ $td }}">Ausgewertete Trainings im Monat</td><td style="{{ $td }}text-align:right;"><strong>{{ $s['monthTrainings'] }}</strong></td></tr>
            <tr><td style="{{ $td }}">Ø Teilnehmende pro Training</td><td style="{{ $td }}text-align:right;"><strong>{{ $s['avgParticipants'] === null ? '–' : number_format($s['avgParticipants'], 1, ',', '.') }}</strong></td></tr>
            <tr><td style="{{ $td }}">Ø Quote Monat</td><td style="{{ $td }}text-align:right;"><strong>{{ $pct($s['avgMonthQuote']) }}</strong></td></tr>
            <tr><td style="{{ $td }}">Ø Quote Jahr</td><td style="{{ $td }}text-align:right;"><strong>{{ $pct($s['avgYearQuote']) }}</strong></td></tr>
            <tr><td style="{{ $td }}">Abmeldungen im Monat</td><td style="{{ $td }}text-align:right;"><strong>{{ $s['cancellations'] }}</strong></td></tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:12px;">
            <tr>
                <th align="left" style="{{ $th }}text-align:left;">Name</th>
                <th align="right" style="{{ $th }}text-align:right;">Monat</th>
                <th align="right" style="{{ $th }}text-align:right;">Monat %</th>
                <th align="right" style="{{ $th }}text-align:right;">Jahr</th>
                <th align="right" style="{{ $th }}text-align:right;">Jahr %</th>
                <th align="center" style="{{ $th }}text-align:center;">Trend</th>
                <th align="right" style="{{ $th }}text-align:right;">Zuletzt</th>
            </tr>
            @foreach ($group['athletes'] as $a)
                <tr>
                    <td style="{{ $td }}">{{ $a['name'] }}</td>
                    <td align="right" style="{{ $td }}text-align:right;">{{ $a['monthCount'] }}</td>
                    <td align="right" style="{{ $td }}text-align:right;">{{ $pct($a['monthQuote']) }}</td>
                    <td align="right" style="{{ $td }}text-align:right;">{{ $a['yearCount'] }}</td>
                    <td align="right" style="{{ $td }}text-align:right;"><strong>{{ $pct($a['yearQuote']) }}</strong></td>
                    <td align="center" style="{{ $td }}text-align:center;">{{ $trendText($a['trend']) }}</td>
                    <td align="right" style="{{ $td }}text-align:right;font-size:12px;">{{ $a['lastAttendance'] ?? '–' }}</td>
                </tr>
            @endforeach
        </table>

        @if (count($group['watch']))
            <h4 style="{{ $h }}">Im Blick behalten</h4>
            <p style="margin:0 0 4px 0;font-size:12px;color:#555555;">Länger als 28 Tage nicht beim Training (Gruppe hatte in dieser Zeit Training).</p>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                @foreach ($group['watch'] as $w)
                    <tr>
                        <td style="{{ $td }}">{{ $w['name'] }}</td>
                        <td style="{{ $td }}">
                            @if ($w['days'] === null)
                                noch nie dabei
                            @else
                                zuletzt {{ $w['lastAttendance'] }} (vor {{ $w['days'] }} Tagen)
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (count($group['absent']))
            <h4 style="{{ $h }}">Aktuell abgemeldet</h4>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                @foreach ($group['absent'] as $ab)
                    <tr>
                        <td style="{{ $td }}">{{ $ab['name'] }}</td>
                        <td style="{{ $td }}">{{ $ab['until'] ? 'bis '.$ab['until'] : 'ohne Enddatum' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (count($group['birthdays']))
            <h4 style="{{ $h }}">Geburtstage im nächsten Monat ({{ $report['nextMonthLabel'] }})</h4>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                @foreach ($group['birthdays'] as $b)
                    <tr>
                        <td style="{{ $td }}">{{ $b['date'] }}</td>
                        <td style="{{ $td }}">{{ $b['name'] }}</td>
                        <td style="{{ $td }}">wird {{ $b['age'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    <p style="margin:24px 0 0 0;font-size:11px;line-height:15px;color:#666666;">Die Quote bezieht sich auf alle ausgewerteten Trainings der Gruppe im Zeitraum. Wer erst im Lauf des Jahres dazugekommen ist, steht deshalb niedriger da, weil kein Eintrittsdatum gespeichert wird. Trend: ▲ besser, ▼ schlechter, = gleich wie im Vormonat. Zuletzt = letzte Teilnahme.</p>
</td></tr>
</table>
</body>
</html>
