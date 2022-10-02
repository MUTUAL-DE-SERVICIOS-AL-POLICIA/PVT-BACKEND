<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body,
        html {
            padding: 10px 10px 3px;
            margin: 0;
            font-family: "Noto sans";
            height: 100%
        }

        body {
            border-radius: .5em;
            border: 1px solid #22292f
        }

        table {
            width: 100%
        }

        thead {
            display: table-header-group
        }

        tfoot {
            display: table-row-group
        }

        tr {
            page-break-inside: avoid
        }

        .table-info {
            border-radius: .5em;
            overflow: hidden;
            border-spacing: 0
        }

        .table-info thead tr td {
            border-left: 1px solid #fff;
            border-top: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975
        }

        .table-info thead tr td:first-child {
            border-radius: .5em 0 0;
            border-left: 1px solid #5d6975
        }

        .table-info thead tr td:last-child {
            border-radius: 0 .5em 0 0;
            border-right: 1px solid #5d6975
        }

        .table-info thead tr td:only-child {
            border-radius: .5em .5em 0 0
        }

        .table-info table thead tr td:last-child {
            border-left: none
        }

        .table-info tbody tr td {
            border-left: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975
        }

        .table-info tbody tr td:last-child {
            border-right: 1px solid #5d6975
        }

        .table-info tbody tr:last-child td:last-child {
            border-right: 1px solid #5d6975;
            border-radius: 0 0 .5em
        }

        .table-info tbody tr:last-child td:first-child {
            border-radius: 0 0 0 .5em
        }

        .table-info tbody tr:last-child td:only-child {
            border-radius: 0 0 1em 1em
        }

        .table-info tfoot tr td {
            border-left: 1px solid #5d6975;
            border-top: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975
        }

        .table-info tfoot tr td:first-child {
            border-radius: .5em 0 0 .5em
        }

        .table-info tfoot tr td:last-child {
            border-radius: 0 .5em .5em 0;
            border-right: 1px solid #5d6975
        }

        .table-info tfoot tr td:only-child {
            border-radius: .5em
        }

        .table-code {
            border-radius: .5em;
            border-spacing: 0
        }

        .table-code tbody tr td:last-child {
            border-right: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975
        }

        .table-code tbody tr td:only-child {
            border-right: 1px solid #5d6975;
            border-left: 1px solid #5d6975
        }

        .table-code tbody tr td:first-child {
            border-bottom: 1px solid #fff
        }

        .table-code tbody tr:first-child td:last-child {
            border-top-right-radius: .5em;
            border-top: 1px solid #5d6975;
            border-right: 1px solid #5d6975
        }

        .table-code tbody tr:first-child td:first-child {
            border-top-left-radius: .5em
        }

        .table-code tbody tr:last-child td:first-child {
            border-bottom-left-radius: .5em;
            border-bottom: none
        }

        .table-code tbody tr:last-child td:last-child {
            border-bottom-right-radius: .5em;
            border-right: 1px solid #5d6975
        }

        .table-code tbody tr:last-child td:only-child {
            border-bottom-right-radius: .5em;
            border-bottom-left-radius: .5em;
            border-right: 1px solid #5d6975;
            border-left: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975
        }

        .table-collapse {
            border-collapse: collapse
        }

        .border,
        .border-grey-darker {
            border-color: #5d6975;
            border-style: solid;
            border-width: 1px
        }

        .no-border {
            border: none !important
        }

        .border-solid {
            border-style: solid
        }

        .border-dashed {
            border-style: dashed
        }

        .border-dotted {
            border-style: dotted
        }

        .border-none {
            border-style: none
        }

        .border-darker {
            border-color: #22292f
        }

        .border-b-2 {
            border-bottom-width: 2px
        }

        .border-b-3 {
            border-bottom-width: 5px
        }

        .border-b-4 {
            border-bottom-width: 4px
        }

        .border-b {
            border-color: #22292f;
            border-style: solid;
            border-bottom-width: 1px
        }

        .border-t {
            border-color: #22292f;
            border-style: solid;
            border-top-width: 1px
        }

        .border-r {
            border-color: #22292f;
            border-style: solid;
            border-right-width: 1px
        }

        .border-l {
            border-color: #22292f;
            border-style: solid;
            border-left-width: 1px
        }

        .inline {
            display: inline
        }

        .inline-block {
            display: inline-block
        }

        .block {
            display: block
        }

        .table {
            display: table
        }

        .table-row {
            display: table-row
        }

        .text-left {
            text-align: left
        }

        .text-center {
            text-align: center
        }

        .text-right {
            text-align: right
        }

        .text-justify {
            text-align: justify
        }

        .w-10 {
            width: 10%
        }

        .w-15 {
            width: 15%
        }

        .w-20 {
            width: 20%
        }

        .w-25 {
            width: 25%
        }

        .w-30 {
            width: 30%
        }

        .w-33 {
            width: 33%
        }

        .w-35 {
            width: 35%
        }

        .w-38 {
            width: 38%
        }

        .w-39 {
            width: 39.5%
        }

        .w-40 {
            width: 40%
        }

        .w-45 {
            width: 45%
        }

        .w-49 {
            width: 49%
        }

        .w-50 {
            width: 50%
        }

        .w-60 {
            width: 60%
        }

        .w-65 {
            width: 65%
        }

        .w-68 {
            width: 68%
        }

        .w-70 {
            width: 70%
        }

        .w-75 {
            width: 75%
        }

        .w-80 {
            width: 80%
        }

        .w-82 {
            width: 82%
        }

        .w-84 {
            width: 84%
        }

        .w-85 {
            width: 85%
        }

        .w-90 {
            width: 90%
        }

        .w-92 {
            width: 92%
        }

        .w-93 {
            width: 93%
        }

        .w-94 {
            width: 94%
        }

        .w-95 {
            width: 95%
        }

        .w-98 {
            width: 98%
        }

        .w-99 {
            width: 99%
        }

        .w-100 {
            width: 100%
        }

        .mw-100 {
            max-width: 100%
        }

        .p-10 {
            padding: 10px
        }

        .p-5 {
            padding: 5px
        }

        .py-100 {
            padding-top: 100px;
            padding-bottom: 100px
        }

        .py-50 {
            padding-top: 50px;
            padding-bottom: 50px
        }

        .py-15 {
            padding-top: 15px;
            padding-bottom: 15px
        }

        .py-10 {
            padding-top: 10px;
            padding-bottom: 10px
        }

        .py-5 {
            padding-top: 5px;
            padding-bottom: 5px
        }

        .py-4 {
            padding-top: 4px;
            padding-bottom: 4px
        }

        .py-3 {
            padding-top: 3px;
            padding-bottom: 3px
        }

        .py-2 {
            padding-top: 2px;
            padding-bottom: 2px
        }

        .px-100 {
            padding-left: 100px;
            padding-right: 100px
        }

        .px-75 {
            padding-left: 75px;
            padding-right: 75px
        }

        .px-50 {
            padding-left: 50px;
            padding-right: 50px
        }

        .px-40 {
            padding-left: 40px;
            padding-right: 40px
        }

        .px-20 {
            padding-left: 20px;
            padding-right: 20px
        }

        .px-15 {
            padding-left: 15px;
            padding-right: 15px
        }

        .px-10 {
            padding-left: 10px;
            padding-right: 10px
        }

        .px-5 {
            padding-left: 5px;
            padding-right: 5px
        }

        .px-4 {
            padding-left: 4px;
            padding-right: 4px
        }

        .px-3 {
            padding-left: 3px;
            padding-right: 3px
        }

        .px-2 {
            padding-left: 2px;
            padding-right: 2px
        }

        .pt-45 {
            padding-top: 45px
        }

        .pt-50 {
            padding-top: 50px
        }

        .pt-60 {
            padding-top: 60px
        }

        .pt-65 {
            padding-top: 65px
        }

        .pt-70 {
            padding-top: 70px
        }

        .pl-45 {
            padding-left: 45px
        }

        .pl-50 {
            padding-left: 50px
        }

        .pl-60 {
            padding-left: 60px
        }

        .pl-65 {
            padding-left: 65px
        }

        .pl-70 {
            padding-left: 70px
        }

        .my-75 {
            margin-top: 75px;
            margin-bottom: 75px
        }

        .my-50 {
            margin-top: 50px;
            margin-bottom: 50px
        }

        .my-10 {
            margin-top: 10px;
            margin-bottom: 10px
        }

        .my-5 {
            margin-top: 5px;
            margin-bottom: 5px
        }

        .m-b-3 {
            margin-bottom: 3px
        }

        .m-b-5 {
            margin-bottom: 5px
        }

        .m-b-10 {
            margin-bottom: 10px
        }

        .m-b-15 {
            margin-bottom: 15px
        }

        .m-b-20 {
            margin-bottom: 20px
        }

        .m-b-25 {
            margin-bottom: 25px
        }

        .m-b-30 {
            margin-bottom: 30px
        }

        .m-b-35 {
            margin-bottom: 35px
        }

        .m-b-50 {
            margin-bottom: 50px
        }

        .m-t-5 {
            margin-top: 5px
        }

        .m-t-10 {
            margin-top: 10px
        }

        .m-t-15 {
            margin-top: 15px
        }

        .m-t-20 {
            margin-top: 20px
        }

        .m-t-25 {
            margin-top: 25px
        }

        .m-t-30 {
            margin-top: 30px
        }

        .m-t-35 {
            margin-top: 35px
        }

        .m-t-40 {
            margin-top: 40px
        }

        .m-t-50 {
            margin-top: 50px
        }

        .m-t-60 {
            margin-top: 60px
        }

        .m-t-65 {
            margin-top: 65px
        }

        .m-t-75 {
            margin-top: 75px
        }

        .m-t-80 {
            margin-top: 80px
        }

        .m-t-85 {
            margin-top: 85px
        }

        .m-t-90 {
            margin-top: 90px
        }

        .m-t-100 {
            margin-top: 100px
        }

        .m-r-5 {
            margin-right: 5px
        }

        .m-r-7 {
            margin-right: 7px
        }

        .m-r-8 {
            margin-right: 8px
        }

        .m-r-10 {
            margin-right: 10px
        }

        .m-r-15 {
            margin-right: 15px
        }

        .m-r-20 {
            margin-right: 20px
        }

        .m-r-25 {
            margin-right: 25px
        }

        .m-r-30 {
            margin-right: 30px
        }

        .m-r-35 {
            margin-right: 35px
        }

        .m-r-50 {
            margin-right: 50px
        }

        .m-r-60 {
            margin-right: 60px
        }

        .m-r-70 {
            margin-right: 70px
        }

        .m-r-75 {
            margin-right: 75px
        }

        .mlr-5 {
            margin-left: 5px
        }

        .m-l-10 {
            margin-left: 10px
        }

        .m-l-15 {
            margin-left: 15px
        }

        .m-l-20 {
            margin-left: 20px
        }

        .m-l-25 {
            margin-left: 25px
        }

        .m-l-30 {
            margin-left: 30px
        }

        .m-l-35 {
            margin-left: 35px
        }

        .m-l-50 {
            margin-left: 50px
        }

        .my-auto {
            margin-top: auto;
            margin-bottom: auto
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto
        }

        .no-paddings {
            padding: 0
        }

        .no-margins {
            margin: 0
        }

        .pin-t {
            top: 0
        }

        .pin-r {
            right: 0
        }

        .pin-b {
            bottom: 0
        }

        .pin-l {
            left: 0
        }

        .pin-y {
            top: 0;
            bottom: 0
        }

        .pin-x {
            right: 0;
            left: 0
        }

        .pin {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0
        }

        .pin-none {
            top: auto;
            right: auto;
            bottom: auto;
            left: auto
        }

        .bg-grey-darker {
            background-color: #5d6975
        }

        .bg-grey-lightest {
            background-color: #dae1e7
        }

        .font-hairline {
            font-weight: 100
        }

        .font-thin {
            font-weight: 200
        }

        .font-light {
            font-weight: 300
        }

        .font-normal {
            font-weight: 400
        }

        .font-medium {
            font-weight: 500
        }

        .font-semibold {
            font-weight: 600
        }

        .font-bold {
            font-weight: 700
        }

        .font-extrabold {
            font-weight: 800
        }

        .font-black {
            font-weight: 900
        }

        .italic {
            font-style: italic
        }

        .uppercase {
            text-transform: uppercase
        }

        .lowercase {
            text-transform: lowercase
        }

        .capitalize {
            text-transform: capitalize
        }

        .normal-case {
            text-transform: none
        }

        .underline {
            text-decoration: underline
        }

        .line-through {
            text-decoration: line-through
        }

        .no-underline {
            text-decoration: none
        }

        .text-black {
            color: #22292f
        }

        .text-white {
            color: #fff
        }

        .text-xxxs {
            font-size: 8px
        }

        .text-xxs {
            font-size: 10px
        }

        .text-xs {
            font-size: 12px
        }

        .text-sm {
            font-size: 14px
        }

        .text-sm-1 {
            font-size: 15px
        }

        .text-base {
            font-size: 16px
        }

        .text-base-1 {
            font-size: 17px
        }

        .text-lg {
            font-size: 18px
        }

        .text-xl {
            font-size: 20px
        }

        .text-2xl {
            font-size: 24px
        }

        .text-3xl {
            font-size: 30px
        }

        .text-4xl {
            font-size: 36px
        }

        .text-5xl {
            font-size: 3rem
        }

        .no-rounded {
            border-radius: 0
        }

        .rounded {
            border-radius: .7rem
        }

        .rounded-t {
            border-top-left-radius: .7rem;
            border-top-right-radius: .7rem
        }

        .rounded-tl {
            border-top-left-radius: .7rem
        }

        .rounded-tr {
            border-top-right-radius: .7rem
        }

        .rounded-bl {
            border-bottom-left-radius: .7rem
        }

        .rounded-br {
            border-bottom-right-radius: .7rem
        }

        .leading-none {
            line-height: 1
        }

        .leading-tight {
            line-height: 1.25
        }

        .leading-normal {
            line-height: 1.5
        }

        .leading-loose {
            line-height: 2
        }

        .list-roman {
            list-style-type: upper-roman
        }

        .align-baseline {
            vertical-align: baseline
        }

        .align-top {
            vertical-align: top
        }

        .align-middle {
            vertical-align: middle
        }

        .align-bottom {
            vertical-align: bottom
        }

        .align-text-top {
            vertical-align: text-top
        }

        .align-text-bottom {
            vertical-align: text-bottom
        }

        .table-striped tr:nth-child(even) {
            background: #f1f5f8
        }

        body {
            counter-reset: number 0 indext 0
        }

        .counter:before {
            counter-increment: number;
            content: counter(number) ".- "
        }

        .intext:before {
            counter-increment: indext;
            content: counter(indext) ""
        }

        .static {
            position: static
        }

        .fixed {
            position: fixed
        }

        .absolute {
            position: absolute
        }

        .relative {
            position: relative
        }

        .sticky {
            position: -webkit-sticky;
            position: sticky
        }
    </style>
</head>

<body class="no-border">
    <div class="w-99">
        <div class="w-95 mx-auto">
            <table class="w-100 table-collapse ">
                <tr>
                    <th class="no-padding no-margins align-middle" style="width:63px; border-top: 2px solid; ">
                        <div class="text-right no-padding no-margins">
                            
                        </div>
                    </th>
                    <th class="align-top text-left text-xs font-normal align-middle px-10"
                        style="border-top: 2px solid; ">
                        Procesado por: <span class="italic">APP MÓVIL</span><br>
                        PLATAFORMA VIRTUAL DE TRÁMITES
                        MUTUAL DE SERVICIOS AL POLICÍA - MUSERPOL <br>
                        <span class="italic">http://www.muserpol.gob.bo</span>
                    </th>
                    <th class="no-padding no-margins align-middle text-right px-10"
                        style="border-top: 2px solid; border-left: 2px solid; ">
                        PVT
                        <br>
                        MUSERPOL
                    </th>
                </tr>
            </table>
        </div>
    </div>
    {{-- <div style="width: 100%;margin:0;paddin:0; display:inline">
        <img src="data:image/png;base64, {{ $bar_code }}" />
    </div>
    <div style="float:right; font-family:sans-serif; font-size:14px;">
        <span>PLATAFORMA VIRTUAL DE TRÁMITES - MUSERPOL &nbsp;</span>
    </div> --}}
</body>

</html>
