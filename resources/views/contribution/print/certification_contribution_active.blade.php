<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Contributions</title>
    <link rel="stylesheet" href="{{ public_path('/css/report-print.min.css') }}" media="all" />
</head>

<body class="no-border">
    <div>
        @include('partials.header', $header)
    </div>

    <div class="text-center">
        <span class="font-medium text-lg">CERTIFICACIÓN DE APORTES</span>
        <p class="text-justify">
            El suscrito Encargado de Cuentas Individuales en base a una revisión de la Base de Datos del Sistema
            Informático de la MUSERPOL de aportes realizados, de:
        </p>
    </div>

    <div>
        @include('affiliate.police_info')
        <p class="font-bold">
            CERTIFICA
        </p>
    </div>

    <div>
        <table class="table-info w-100 text-center">
            <thead class="bg-grey-darker text-xxs text-white">
                <tr class="text-white text-xxs">
                    <th class="data-row py-2">AÑO</th>
                    <th class="data-row py-2">MES</th>
                    <th class="data-row py-2">AP. FONDO DE RETIRO</th>
                    <th class="data-row py-2">AP. QUOTA MORTUORIA</th>
                    <th class="data-row py-2">TOTAL COTIZABLE</td>
                    <th class="data-row py-2">APORTE</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($contributions as $contribution)
                    <tr>
                        <td class="data-row py-2">{{ date('Y', strtotime($contribution->month_year)) }}</td>
                        <td class="data-row py-2">{{ date('m', strtotime($contribution->month_year)) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->retirement_fund) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->mortuary_quota) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->quotable) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
    <div>
        <table class="table-info w-100">
            <thead>
                <tr class="bg-grey-darker text-xxs text-white">
                    <th class="w-10 text-justify">
                        <p>NOTA: Toda vez que, la presente certificación detalla información referencial
                            respecto a los aportes para el beneficio del Auxulio Mortuorio, se requiere al solicitante
                            efectuar la verificación correspondiente de los datos, a fin de no existir reclamos
                            posteriores.</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="w-10 text-xxs text-justify">Asímismo, se efectuó la revisión de datos contenidos en el
                        Sistema Institucional y base de
                        datos antecedentes respecto a los aportes efectuados para el beneficio.
                        En cuanto se certifica para fines consiguientes.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>
    <div>
        @include('partials.footer_app', $header)
    </div>
</body>

</html>
