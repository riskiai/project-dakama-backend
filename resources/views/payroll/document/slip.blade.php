@php
    use Carbon\Carbon;
    $attendances = array_slice($slip['records'], 0, 7);
    $missing = 7 - count($attendances);
@endphp

<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        {!! file_get_contents(public_path('css/tailwind.css')) !!}
    </style>
</head>

<body class="font-sans p-6">
    <div class="border-2 border-black">
        <!-- Info Karyawan -->
        <div class="pb-5">
            <table class="w-full">
                <tr>
                    <td class="p-1 text-left text-lg font-bold" colspan="3">
                        {{ $slip['company_name'] }}
                    </td>
                </tr>
                <tr class="text-[12px]">
                    <td class="p-1 text-left">Nama</td>
                    <td class="p-1 text-left">: {{ $slip['target_name'] }}</td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left">Tanggal</td>
                    <td class="p-1 text-left">: {{ Carbon::parse(explode(', ', $slip['range_date'])[0])->format('j') }}
                        sd {{ Carbon::parse(explode(', ', $slip['range_date'])[1])->format('j F Y') }}</td>
                </tr>
                <tr class="text-[12px]">
                    <td class="p-1 text-left">Poss</td>
                    <td class="p-1 text-left">: {{ $slip['target_poss'] }}</td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left">Project</td>
                    <td class="p-1 text-left">: {{ $slip['last_project'] }}</td>
                </tr>
                <tr class="text-[12px]">
                    <td class="p-1 text-left font-bold">Transfer To</td>
                    <td class="p-1 text-left font-bold">
                        : {{ $slip['payout_account'] }}
                    </td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                    <td class="p-1 text-left"></td>
                </tr>
            </table>
        </div>

        <!-- Absensi -->
        <?php
// Potong data jadi per 7 kolom
$chunks = array_chunk($slip["records"], 7);
// dd($chunks);
 $missing = 7 - count($chunks);
 $totalJHK = [];
 $totalJJL = 0;
// Render tabel
foreach ($chunks as $record) {
?>
        <table style="table-layout: fixed" class="w-full border-collapse">
            <thead class="border-y border-black">
                <tr class="text-[12px]">
                    <td class="border-r border-t border-black p-1">Tgl</td>
                    <?php foreach ($record as $d): ?>
                    <td class="border-r border-t border-black p-1 text-center">
                        <?= $d['date'] ?>
                    </td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-t border-black p-1" colspan="2"></td>
                </tr>
                <tr class="text-[12px]">
                    <td class="border-r border-black p-1">Hari</td>
                    <?php foreach ($record as $d): ?>
                    <td
                        class="border-r border-t border-black p-1 text-center {{ $d['is_weekend'] ? 'bg-red-600' : '' }}">
                        <?= $d['day_name_single'] ?>
                    </td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-t border-black p-1" colspan="2"></td>
                </tr>
            </thead>
            <tbody>
                <tr class="text-[12px]">
                    <td class="border-r border-t border-black p-1">Customer</td>
                    <?php foreach ($record as $d): ?>
                    <td class="border-r border-t border-black p-1 text-center">
                        {{ $d['attendance'] ? $d['attendance']->project->company->contactType->name : '' }}
                    </td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-r border-t border-black p-1"></td>
                    <td class="border-t border-black p-1"></td>
                </tr>
                <tr class="text-[12px]">
                    <td class="border-r border-t border-black p-1">Project</td>
                    <?php foreach ($record as $d): ?>
                    <td class="border-r border-t border-black p-1 text-center">
                        {{ $d['attendance'] ? $d['attendance']->project->name : '' }}</td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-r border-t border-black p-1"></td>
                    <td class="border-t border-black p-1"></td>
                </tr>
                <tr class="text-[12px]">
                    <td class="border-t border-r border-black p-1">JHK</td>
                    @php
                        $totalJHK = 0;
                    @endphp
                    <?php foreach ($record as $d): ?>
                    @php
                        if ($d['attendance']) {
                            $totalJHK += 1;
                        }
                    @endphp
                    <td class="border-r border-t border-black p-1 text-center">{{ $d['attendance'] ? 1 : '' }}</td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-r border-t border-black p-1 text-right">{{ $totalJHK }}</td>
                    <td class="border-t border-black p-1">Hari</td>
                </tr>
                <tr class="text-[12px]">
                    <td class="border-b border-r border-black p-1">JJL</td>
                    @php
                        $dataOvertimes = 0;
                    @endphp
                    <?php foreach ($record as $d): ?>
                    <td class="border-r border-t border-b border-black p-1 text-center">
                        @if ($d['overtime'])
                            @php
                                $dataOvertimes += $d['overtime']->duration;
                                $totalJJL += $dataOvertimes;
                            @endphp
                            {{ $d['overtime']->duration }}
                        @endif
                    </td>
                    <?php endforeach; ?>

                    <?php for ($i = count($record); $i < 7; $i++): ?>
                    <td class="border-r border-t border-b border-black p-1"></td>
                    <?php endfor; ?>

                    <td class="border-r border-t border-b border-black p-1 text-right">{{ $dataOvertimes }}</td>
                    <td class="border-t border-b border-black p-1">Jam</td>
                </tr>
            </tbody>
        </table>
        <br>
        <?php
}

$totalAmount = 0;

// $totalAmount += count($totalJHK) * $slip['reports'][0]['rate'];
// $totalAmount += $totalJJL * $slip['reports'][1]['rate'];
// $totalAmount += count($totalJHK) * $slip['reports'][2]['rate'];
// $totalAmount += $slip['bonus_jhk'] * $slip['reports'][3]['rate'];
?>

        <!-- Rekap -->
        <table class="w-full my-3 border-collapse">
            @foreach ($slip['reports'] as $item)
                @php
                    $totalAmount += $item['total'];
                @endphp
                <tr class="text-[12px]">
                    <td class="p-1">{{ $item['label'] }}</td>
                    <td class="p-1 text-right">{{ $item['amount'] }}</td>
                    <td class="p-1 text-right">X</td>
                    <td class="p-1 text-right">Rp</td>
                    <td class="p-1 text-right">{{ number_format($item['rate'], 0, ',', '.') }}</td>
                    <td class="p-1 text-right">Rp</td>
                    <td class="text-right p-1">
                        {{ number_format($item['total'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            @foreach ($slip['report_others'] as $item)
                @php
                    $totalAmount += $item['total'];
                @endphp
                <tr class="text-[12px]">
                    <td class="p-1" colspan="5">{{ $item['label'] }}</td>
                    <td class="p-1 text-right">Rp</td>
                    <td class="text-right p-1">
                        {{ number_format($item['total'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </table>
        <table class="w-full font-bold border-collapse mb-3">
            <tr class="text-[12px]">
                <td class="p-1 text-left">Jumlah</td>
                <td class="p-1 text-right">Rp</td>
                <td class="p-1 text-right">{{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>
        </table>
        <table class="w-full text-red-600 italic border-collapse">
            <tr class="text-[12px]">
                <td class="p-1 border-b border-t border-black text-left">Kasbon</td>
                <td class="p-1 border-t border-b border-black text-left">Sisa Kasbon</td>
                <td class="p-1 border-t border-b border-black text-right">Rp</td>
                <td class="p-1 border-t border-b border-black text-right">
                    {{ number_format($slip['kasbon'], 0, ',', '.') }}</td>
            </tr>
            @foreach ($slip['kasbon_list'] as $kasbon)
                <tr class="text-[12px]">
                    <td class="p-1 border-b border-r border-black text-right" style="padding-right: 20px">
                        {{ $kasbon->payment_method }}</td>
                    <td class="p-1 border-b border-black text-left" style="padding-left: 20px">
                        {{ Carbon::parse($kasbon->payment_at)->format('d-M') }}</td>
                    <td class="p-1 border-b border-black text-right">Rp</td>
                    <td class="p-1 border-b border-black text-right">
                        {{ number_format($kasbon->decrease, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="text-[12px]">
                <td class="p-1 border-b border-r border-black text-right" style="padding-right: 20px; color: white">
                    test</td>
                <td class="p-1 border-b border-black text-left" style="padding-left: 20px; color: white">
                    test</td>
                <td class="p-1 border-b border-black text-right" style=" color: white">Rp</td>
                <td class="p-1 border-b border-black text-right" style=" color: white">
                    test</td>
            </tr>
        </table>
        <table class="w-full font-bold border-collapse">
            <tr class="text-[12px]">
                <td class="p-1 border-y border-r border-black text-left">Total Bayar</td>
                <td class="p-1 border-black text-left">Rp</td>
                <td class="p-1 border-black text-right">
                    {{ number_format($totalAmount - $slip['kasbon'], 0, ',', '.') }}</td>
            </tr>
        </table>
        <table class="w-full font-bold border-collapse ">
            <tr class="text-[12px]">
                <td class="p-1 text-center border-t" style="background-color: yellow">Catatan : {{ $slip['notes'] }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
