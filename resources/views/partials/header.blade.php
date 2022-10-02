<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>

<body class="w-100">
    <table class="uppercase">
        <tr>
            <th class="w-20 text-left no-padding no-margins align-middle">
                <div class="text-left">
                    <img src="{{ public_path("/img/logo.png") }}" class="w-75">
                </div>
            </th>
            <th class="w-60 align-top">
                <div class="font-hairline leading-tight text-xs" >
                    <div>MUTUAL DE SERVICIOS AL POLICÍA "MUSERPOL"</div>
                    <div>{{ $direction }}</div>
                    <div>{{ $unity }}</div>
                </div>
            </th>
            <th class="w-20 no-padding no-margins align-top">
                <table class="table-code no-padding no-margins text-xxxs uppercase">
                    @if (isset($table))
                    @if (count($table) > 0)
                    <tbody>
                        @foreach ($table as $row)
                        <tr>
                            <td class="text-center bg-grey-darker text-white">{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @endif
                    @endif
                </table>
            </th>
        </tr>
    </table>
    <hr class="m-b-10" style="margin-top: 0; padding-top: 0;">
</body>
</html>
<style>
    html {
    /* Arriba | Derecha | Abajo | Izquierda */
    margin: 5pt 15pt 16pt 15pt ;
    }
</style>
