<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="{{ public_path('/css/report-print.min.css') }}" media="all" />
    <link rel="icon" href="<%= BASE_URL %>favicon.ico">
</head>
<body style="margin: 0 0 0 0;">
    @for ($it = 0; $it < $copies; $it++)
        @php($n = 1)
        <div style="height: 48%; margin:0px;">
            @include('partials.header', $header)
            <div class="block">
                <div class="font-semibold leading-tight text-center m-b-10 text-xs">{{ $title }}</div>
            </div>
            <div class="block">
                <div class="font-semibold leading-tight text-left m-b-10 text-xs">{{ $n++ }}. DATOS DEL TITULAR
                </div>
            </div>
            <div class="block">
                <table class="table-info w-100 text-center uppercase my-20">
                    <tr class="bg-grey-darker text-xxs text-white">
                        <td class="w-50">Solicitante</td>
                        <td class="w-15">CI</td>
                        <td class="w-15">Estado</td>
                    </tr>
                    <tr>
                        @if ($user->dead && $user->spouse)
                            <td class="data-row py-5"> {{ $user->spouse->full_name }}</td>
                            <td class="data-row py-5">{{ $user->spouse->identity_card }}</td>
                        @else
                            <td class="data-row py-5"> {{ $user->full_name }}</td>
                            <td class="data-row py-5">{{ $user->identity_card_ext }}</td>
                        @endif
                        <td class="data-row py-5">{{ $user->affiliate_state->affiliate_state_type->name }}</td>
                    </tr>
                    <tr class="bg-grey-darker text-xxs text-white">
                        <td class="w-50">Celular</td>
                        <td class="w-50">Celular de Activacion</td>
                        <td class="w-20">Fecha de activacion</td>
                    </tr>
                    <tr>
                        <td class="data-row py-5">{{ $user->cell_phone_number }}</td>
                        <td class="data-row py-5">{{ substr($user->cell_phone_number,0,11);}}</td>
                        <td class="data-row py-5">{{ $fecha }}</td>
                    </tr>
                    <tr class="bg-grey-darker text-xxs text-white">
                        <td class="w-70">Unidad</td>
                        <td colspan="2">Estado de credenciales</td>
                    </tr>
                    <tr>
                        <td class="data-row py-5">{{ $header['unity'] }}</td>
                        <td colspan="2" class="data-row py-5">{{ $credential->access_status }}</td>
                    </tr>
                </table>
                <p>
                    Por medio de la presente, autorizo expresamente a la MUSERPOL a una vez instalada la aplicación
                    MUSERPOL
                    PVT (OFICINA VIRTUALSEGUIMIENTO DE TRAMITE) en mi dispositivo móvil, pueda realizar la activación a
                    través del envío de las credenciales correspondientes
                    mediante mensaje SMS.
                </p>
            </div>
            <div>
                <table style="font-size:11px; ">
                    <tbody>
                        @foreach ($signers->chunk(2) as $chunk)
                            <tr>
                                @foreach ($chunk as $person)
                                    <td>
                                        @include('partials.signature_box', $person)
                                    </td>
                                @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if ($it != 1)
            <br>
        @endif
    @endfor
</body>

</html>
