<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - PT KUBIKA</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px auto;
            width: 600px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        p {
            font-size: 16px;
            color: #555;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>PT KUBIKA</h1>
        </header>

        <section>
            <h3>Hello, {{ $user->name }}</h3>
            <p><strong>Your password has been successfully reset.</strong></p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>New Password:</strong> {{ $newPassword }}</p>
            <p><strong>Login here:</strong> <a href="https://damakaryamakmur.com/login" target="_blank">damakaryamakmur.com</a></p>
            <p>Please make sure to change your password after logging in for security purposes.</p>
            <p>Thank you for using our service.</p>
        </section>

        <footer>
            <p>Best regards, <br> PT DAKAMA</p>
        </footer>
    </div>
</body>
</html>
