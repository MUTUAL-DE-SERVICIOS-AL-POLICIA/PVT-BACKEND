<table class="m-t-35">
    <tr>
        <td class="no-border text-center text-base w-50 align-bottom">
            <span class="font-bold">
                ----------------------------------------------------
            </span>
        </td>
        <td class="no-border text-center text-base w-50 align-bottom">
            <span class="font-bold">
                ----------------------------------------------------
            </span>
        </td>
    </tr>
    <tr>
        <td class="no-border text-center text-base w-50 align-top">
            <span class="font-bold">SOLICITANTE</span>
        </td>
        <td class="no-border text-center text-base w-50">
            <span class="font-bold block">{!! strtoupper($user->fullName) !!}</span>
            <div class="text-xs text-center" style="width: 350px; margin:0 auto;">
                {!! $user->position !!}</div>
        </td>
    </tr>
</table>
