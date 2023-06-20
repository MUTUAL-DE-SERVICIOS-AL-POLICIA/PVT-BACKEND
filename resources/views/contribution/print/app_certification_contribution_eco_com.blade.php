<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Contributions</title>
    <link rel="stylesheet" href="{{ public_path('/css/report-print.min.css') }}" media="all" />
    <style>
        body:before {
            content: 'NO VÁLIDO PARA TRÁMITES ADMINISTRATIVOS';
            position: fixed;
            z-index: -1;
            color: #9b9b9b;
            font-size: 65px;
            font-weight: 500px;
            display: grid;
            opacity: 0.3;
            transform: rotate(-30deg);

            top: 35%;
            left: 18%;
            bottom: 30%;
            right: 18%;
            text-align: center;
        }
    </style>
</head>

<body class="no-border">
    <div>
        @include('partials.header_app', $header)
    </div>

    <div class="text-center">
        <span class="font-medium text-lg">CERTIFICACIÓN DE APORTES</span>
        <p class="text-justify">
            El suscrito Encargado de Cuentas Individuales en base a una revisión de la Base de Datos del Sistema
            Informático
            de la MUSERPOL de aportes realizados, de:
        </p>
    </div>

    <div>
        @include('affiliate.police_info')

        @if ($value)
            <div>
                <p class="font-bold">DATOS DEL(A) VIUDO(A)</p>
                @include('spouse.spouse_info')
            </div>
        @endif
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
                    <th class="data-row py-2">TITULAR/VIUDA</th>
                    <th class="data-row py-2" colspan="2">MODALIDAD DE PAGO</th>
                    <th class="data-row py-2">TOTAL COTIZABLE</td>
                    <th class="data-row py-2">APORTE</td>
                </tr>
            </thead>
            <tbody class="text-xxs">
                @foreach ($contributions as $contribution)
                    <tr>
                        <td class="data-row py-2">{{ $num = $num + 1 }}</td>
                        <td class="data-row py-2">{{ $contribution['year'] }}</td>
                        <td class="data-row py-2">{{ $contribution['month'] }}</td>
                        <td class="data-row py-2">{{ $contribution['rent_class'] }}</td>
                        <td class="data-row py-2" colspan="2">{{ $contribution['description'] }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution['rent_pension']) }}</td>
                        <td class="data-row py-2">{{ Util::money_format($contribution['total']) }}</td>
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
                            respecto a los aportes para el beneficio del Auxilio Mortuorio, se requiere al solicitante
                            efectuar la verificación correspondiente de los datos, a fin de no existir reclamos
                            posteriores.</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="w-10 text-xxs text-justify">
                        @if ($text == 'Descuento SENASIR')
                            Asímismo, se efectuó la revisión de datos contenidos en el Sistema Institucional y base de
                            datos respecto a los aportes efectuados para el beneficio de Auxilio Mortuorio mediante
                            Descuentos
                            Mensuales de las boletas de pago de Renta (información proporcionada por el SENASIR a partir
                            de la gestión 1999 en adelante). Este documento no es válido para trámites administrativos,
                            siendo de uso exclusivo para la MUSERPOL.
                        @else
                            Asimismo, se efectuó la revisión de datos contenidos en el Sistema Institucional y base de
                            datos respecto a los aportes efectuados para el beneficio de Auxilio Mortuorio soló por la
                            modalidad de Descuento Anticipado del Complemento Económico Semestral. Este documento no es
                            válido para trámites administrativos, siendo de uso exclusivo para la MUSERPOL.
                        @endif
                        <br>Es cuanto se certifica para fines consiguientes.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>
</body>

</html>
