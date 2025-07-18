<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Approval {{ $data->title }}</title>
</head>

<body style="font-family: 'Segoe UI', sans-serif; background-color: #f9fafb; padding: 24px;">
    <div
        style="max-width: 600px; margin: auto; background-color: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); overflow: hidden;">
        <div style="background-color: #1d4ed8; padding: 20px;">
            <h2 style="color: white; margin: 0; font-size: 20px;">🔔 {{ $data->title }}</h2>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 16px; margin-bottom: 16px; color: #111827;">
                Hai <strong>{{ $user->name }}</strong>,
            </p>
            <p style="font-size: 16px; color: #374151; margin-bottom: 16px;">
                Ada {{ $data->title }} baru yang memerlukan persetujuan Anda. Silakan tinjau detail berikut:
            </p>

            <div style="background-color: #f3f4f6; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 16px;"><strong>Judul:</strong> {{ $data->description }}</p>
                <p style="margin: 0; font-size: 16px;"><strong>Tanggal:</strong>
                    {{ $data->created_at->format('d F Y, H:i a') }}</p>
                <p style="margin: 0; font-size: 16px;"><strong>Diajukan oleh:</strong> {{ $data->requestBy->name }}</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $link }}"
                    style="display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    Lihat & Setujui
                </a>
            </div>

            <p style="margin-top: 32px; font-size: 14px; color: #6b7280;">
                Jika Anda merasa tidak terkait dengan email ini, silakan abaikan notifikasi ini.
            </p>
        </div>
    </div>
</body>

</html>
