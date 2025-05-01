<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Facades\MessageDakama;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        DB::beginTransaction();

        try {
            // Ambil token dari header Authorization
            $token = $request->bearerToken();
            
            // Hapus token dari tabel personal_access_tokens secara langsung
            if ($token) {
                $hashedToken = hash('sha256', $token);
                
                // Cari token di tabel personal_access_tokens dan hapus
                $personalAccessToken = PersonalAccessToken::where('token', $hashedToken)->first();
                
                if ($personalAccessToken) {
                    $personalAccessToken->delete();
                }
            }

            DB::commit();
            return MessageDakama::success('Logout successfully!');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
