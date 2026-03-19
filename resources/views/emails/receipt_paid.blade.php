<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
</head>

<body style="font-family: Arial, sans-serif; color:#111;">
    <p>Olá {{ $receipt->member->nome ?? 'Sócio' }},</p>

    <p>
        Confirmamos o pagamento da quota do ano <strong>{{ $receipt->ano }}</strong>.
    </p>

    <p>
        Em anexo segue o recibo <strong>{{ $receipt->numero }}</strong>.
    </p>

    <p>
        Obrigado,<br>
        <strong>SMSA</strong>
    </p>
</body>

</html>
