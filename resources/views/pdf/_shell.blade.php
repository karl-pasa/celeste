{{-- Shared chrome for the portrait documents: letterhead, security footer, QR block. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 44px 96px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #16233f; line-height: 1.6; }

        .letterhead { text-align: center; border-bottom: 2px solid #12224f; padding-bottom: 10px; margin-bottom: 4px; }
        .republic { font-size: 8pt; color: #5b6784; letter-spacing: .5px; }
        .institution { font-size: 15pt; font-weight: bold; letter-spacing: 2.4px; color: #12224f; margin: 3px 0 1px; }
        .campus { font-size: 8.5pt; letter-spacing: 1.6px; color: #24417f; }
        .office { font-size: 8pt; color: #5b6784; margin-top: 2px; }

        .doc-title {
            text-align: center;
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 3px;
            color: #12224f;
            margin: 22px 0 4px;
        }
        .doc-sub { text-align: center; font-size: 8pt; color: #5b6784; margin-bottom: 20px; }

        p { margin: 0 0 11px; text-align: justify; }
        .indent { text-indent: 34px; }
        .name-inline { font-weight: bold; text-transform: uppercase; }

        table.fields { width: 100%; border-collapse: collapse; margin: 12px 0 16px; }
        table.fields td { padding: 5px 0; font-size: 9pt; vertical-align: top; }
        table.fields td.label { width: 34%; color: #5b6784; }
        table.fields td.value { font-weight: bold; }

        table.grades { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8pt; }
        table.grades th {
            background: #f4f7fc; border: 1px solid #d8e0ee; padding: 5px 6px;
            font-size: 7pt; text-transform: uppercase; letter-spacing: .6px; color: #24417f;
        }
        table.grades td { border: 1px solid #e3e8f1; padding: 4px 6px; }
        table.grades td.num { text-align: center; }
        table.grades tr.term td {
            background: #eef2f9; font-weight: bold; color: #12224f;
            font-size: 7.5pt; letter-spacing: .5px;
        }

        .sig-block { margin-top: 34px; }
        .sig-line { border-top: 1px solid #12224f; width: 210px; padding-top: 4px; }
        .sig-name { font-size: 9pt; font-weight: bold; }
        .sig-role { font-size: 7.5pt; color: #5b6784; }

        .security {
            position: fixed; bottom: -78px; left: 0; right: 0;
            border-top: 1px solid #d8e0ee; padding-top: 7px;
        }
        .security td { vertical-align: top; font-size: 6.6pt; color: #5b6784; line-height: 1.5; }
        .security img { width: 74px; height: 74px; }
        .serial { font-family: 'DejaVu Sans Mono', monospace; font-size: 7pt; color: #12224f; font-weight: bold; }
        .hash { font-family: 'DejaVu Sans Mono', monospace; font-size: 5.4pt; color: #8a94ad; word-wrap: break-word; }
        .notice { font-size: 6.4pt; color: #5b6784; }
    </style>
</head>
<body>

<div class="letterhead">
    <div class="republic">Republic of the Philippines</div>
    <div class="institution">{{ mb_strtoupper($payload['institution']) }}</div>
    <div class="campus">{{ mb_strtoupper($payload['campus']) }}</div>
    <div class="office">Office of the University Registrar</div>
</div>

@yield('document')

{{-- Security footer, repeated on every page --}}
<table class="security" cellpadding="0" cellspacing="0">
    <tr>
        <td width="82"><img src="{{ $qr }}" alt="Verification QR code"></td>
        <td style="padding-left:8px">
            <span class="serial">{{ $certificate->serial_number }}</span><br>
            Scan the code or enter this serial at {{ config('app.url') }}/verify to confirm this document
            was issued by {{ $payload['institution'] }} and has not been altered.<br>
            <span class="hash">SHA-256 {{ $certificate->content_hash }}</span><br>
            <span class="notice">
                Not valid without the dry seal of the University. Any erasure or alteration voids this document.
            </span>
        </td>
    </tr>
</table>

</body>
</html>
