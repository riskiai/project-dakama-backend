<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Divisi;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Divisi\CreateRequest;
use App\Http\Requests\Divisi\UpdateRequest;
use App\Http\Resources\Divisi\DivisiCollection;

class DivisiController extends Controller
{
    public function index(Request $request)
    {
        $query = Divisi::query();

        // Filter pencarian berdasarkan 'id' atau 'name'
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter berdasarkan 'kode_divisi'
        if ($request->has('kode_divisi')) {
            $query->where('kode_divisi', 'like', '%' . $request->kode_divisi . '%');
        }

        // Filter berdasarkan tanggal (range)
        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);
            $query->whereBetween('created_at', [$date[0], $date[1]]);
        }

        // Paginate hasil query berdasarkan jumlah per halaman
        $divisi = $query->paginate($request->per_page);

        return new DivisiCollection($divisi);
    }

    public function divisiall(Request $request)
    {
        $query = Divisi::query();

        // Filter pencarian berdasarkan 'id' atau 'name'
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter berdasarkan 'kode_divisi'
        if ($request->has('kode_divisi')) {
            $query->where('kode_divisi', 'like', '%' . $request->kode_divisi . '%');
        }

        // Filter berdasarkan tanggal (range)
        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);
            $query->whereBetween('created_at', [$date[0], $date[1]]);
        }

        // Paginate hasil query berdasarkan jumlah per halaman
        $divisi = $query->get();

        return new DivisiCollection($divisi);
    }

    public function show(string $id)
    {
        // Mengambil data divisi berdasarkan ID
        $divisi = Divisi::find($id);
        
        // Jika divisi tidak ditemukan
        if (!$divisi) {
            return MessageDakama::notFound('Data not found!');
        }

        // Mengembalikan data divisi yang ditemukan
        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => [
                'id' => $divisi->id,
                'name' => $divisi->name,
                'kode_divisi' => $divisi->kode_divisi, // Mengganti 'kode_kategori' dengan 'kode_divisi'
                'created_at' => $divisi->created_at,
                'updated_at' => $divisi->updated_at,
            ]
        ]);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $divisi = Divisi::create([
                'name' => $request->name,
            ]);

            DB::commit();
            return MessageDakama::success("Divisi {$divisi->name} has been successfully created with kode_divisi {$divisi->kode_divisi}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        // Cari divisi berdasarkan ID
        $divisi = Divisi::find($id);
        if (!$divisi) {
            return MessageDakama::notFound('Divisi not found!');
        }

        try {
            // Ambil nama baru dari request, atau gunakan nama lama jika tidak ada perubahan
            $newName = $request->input('name', $divisi->name);

            // Log nama baru untuk debugging
            Log::info('Nama Baru: ' . $newName);

            // Generate kode singkatan baru berdasarkan nama baru
            $firstChar = substr($newName, 0, 1);
            $middleChar = substr($newName, (int)(strlen($newName) / 2), 1);
            $lastChar = substr($newName, -1);
            $nameSlug = strtoupper($firstChar . $middleChar . $lastChar);

            // Pisahkan kode divisi lama
            $kodeParts = explode('-', $divisi->kode_divisi);

            // Log kode divisi lama dan hasil pemecahan
            Log::info('Kode Divisi Lama: ' . $divisi->kode_divisi);
            Log::info('Kode Parts: ' . json_encode($kodeParts));

            // Validasi apakah kode divisi memiliki format yang benar
            if (count($kodeParts) === 3) {
                $incrementPart = $kodeParts[1]; // Bagian nomor increment tetap sama
            } else {
                // Jika format tidak sesuai, gunakan default
                $incrementPart = '001';
            }

            // Buat kode divisi baru hanya dengan mengubah singkatan nama
            $newKodeDivisi = "{$nameSlug}-{$incrementPart}";

            // Log kode divisi yang baru
            Log::info('Kode Divisi Baru: ' . $newKodeDivisi);

            // Perbarui divisi dengan nama dan kode_divisi yang baru
            $divisi->update([
                'name' => $newName,
                'kode_divisi' => $newKodeDivisi, // Update kode divisi
            ]);

            DB::commit();
            return MessageDakama::success("Divisi {$divisi->name} has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error updating divisi: ' . $th->getMessage());
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
    
        // Cari divisi berdasarkan ID
        $divisi = Divisi::find($id);
        if (!$divisi) {
            return MessageDakama::notFound('Divisi not found!');
        }
    
        try {
            User::where('divisi_id', $id)->update(['divisi_id' => null]);

            // Hapus divisi
            $divisi->delete();
    
            // Commit transaksi jika berhasil
            DB::commit();
            return MessageDakama::success("Divisi {$divisi->name} has been deleted");
        } catch (\Throwable $th) {
            // Rollback jika ada error
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
