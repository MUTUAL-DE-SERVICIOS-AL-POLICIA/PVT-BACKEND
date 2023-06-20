<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>

<body class="w-100">
    <div style="margin-top: 50; text-align: center">
        <div>
            <hr style="margin-top: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0;" width="250px">
        </div>
        <div>
            {{ $full_name }}
        </div>
        @if(isset($identity_card))
            <div>
                C.I. {{ $identity_card }}
            </div>
        @endif
        <div class="font-bold">
            {{ $position }}
        </div>
        @if(isset($employee))
            @if($employee)
                <div>
                    MUSERPOL
                </div>
            @endif
        @endif
    </div>
</body>
</html>
