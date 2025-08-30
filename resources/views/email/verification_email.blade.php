<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>

    <style>
        body {
            background-color: grey;
        }
    </style>
</head>

<body>

    <div class="container">
        <header>
            <h1>PT DAKAMA</h1>
        </header>
        <section>
            <h3>Halo, {{ $user->name }}</h3>
            <p><strong>Akun Anda telah berhasil didaftarkan.</strong></p>
            <p><strong>Email : {{ $user->email }}</strong></p>
            <p><strong>Password : {{ $user->passwordRecovery }}</strong></p>
            <p><strong>Link Website : </strong> <a href="https://damakaryamakmur.com/login" style="text-decoration: none; font-size:13px; font-weight:bold; color:blue;">dakama.id</a></p>
            <p>Terima kasih telah menggunakan layanan kami.</p>

            <h3>Salam hormat, <br> PT DAKAMA</h3>
        </section>
        <footer>
            <br>
            <p><strong>Terima kasih</strong></p>
        </footer>
    </div>

</body>

</html>
