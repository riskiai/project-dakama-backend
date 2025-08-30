<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Verification</title>
</head>

<body>
    <div class="container">
        <header>
            <h1>PT DAKAMA</h1>
        </header>
        <section>
            <h3>Halo, {{ $user->name }}</h3>
            <p><strong>Berikut adalah token verifikasi Anda untuk reset password:</strong></p>
            <p><strong>Token: {{ $token }}</strong></p>
            <p><strong>Silakan klik tautan berikut untuk melanjutkan reset password:</strong></p>
          {{--   <p><a href="{{ url('verify-token/' . $token) }}" style="text-decoration: none; font-size:13px; font-weight:bold; color:blue;">Verifikasi Token</a></p> --}}
            <p><strong>Link Website : </strong> <a href="https://damakaryamakmur.com/change-password/v1/confirmation" style="text-decoration: none; font-size:13px; font-weight:bold; color:blue;">kubikaexpo.id</a></p>
            <p>Jika Anda tidak merasa meminta reset password, abaikan email ini.</p>

            <h3>Salam hormat, <br> PT DAKAMA</h3>
        </section>
        <footer>
            <br>
            <p><strong>Terima kasih</strong></p>
        </footer>
    </div>
</body>

</html>
