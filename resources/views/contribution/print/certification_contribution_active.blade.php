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

    <div class="block">
        <table class="table-info w-100 text-center">
            <thead class="bg-grey-darker text-xxs text-white">
                <tr class="text-white text-xxs">
                    <th class="data-row py-2">N°</th>
                    <th class="data-row py-2">AÑO</th>
                    <th class="data-row py-2">MES</th>
                    <th class="data-row py-2">TOTAL COTIZABLE</td>
                    <th class="data-row py-2">AP. FONDO DE RETIRO</th>
                    <th class="data-row py-2">AP. CUOTA MORTUORIA</th>
                    <th class="data-row py-2">APORTE</td>
                </tr>
            </thead>
            <tbody class="text-xxs">
                @foreach ($contributions as $contribution)
                    <tr>
                        <td class="data-row py-2">{{ $num = $num + 1 }}</td>
                        <td class="data-row py-2">{{ date('Y', strtotime($contribution->month_year)) }}</td>
                        <td class="data-row py-2">{{ date('m', strtotime($contribution->month_year)) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->quotable) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->retirement_fund) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->mortuary_quota) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution->total) }}</td>
                    </tr>
                    @foreach ($reimbursements as $reimbursement)
                        @if ($contribution->month_year == $reimbursement->month_year)
                            <tr>
                                <td class="data-row py-2"></td>
                                <td class="data-row py-2">Ri</td>
                                <td class="data-row py-2">{{ date('m', strtotime($reimbursement->month_year)) }}</td>
                                <td class="data-row py-2">{{ Util::money_format($reimbursement->quotable) }}</td>
                                <td class="data-row py-2">{{ Util::money_format($reimbursement->retirement_fund) }}
                                </td>
                                <td class="data-row py-2">{{ Util::money_format($reimbursement->mortuary_quota) }}</td>
                                <td class="data-row py-2">{{ Util::money_format($reimbursement->total) }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
    <div class="text-justify border-grey-darker rounded">
        <table class="w-100 text-xxs p-10">
            <tbody>
                <tr>
                    <td>
                        <b>NOTA.- </b>La presente certificación contiene informacion de aportes registrados en la Base de
                        Datos
                        de la MUSERPOL (considerando la existencia de registros de aportes en el Sistema Institucional a
                        partir de Febrero de 1999 en adelante). Asímismo, este documento no contempla toda la
                        información de la planilla de pago, por lo que no es válido para trámites administrativos,
                        siendo de uso exclusivo de la MUSERPOL.<br>
                        Es cuanto se certifica, para fines consiguientes.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>
    <div>
        @include('partials.signature_footer')
    </div>
</body>

</html>
