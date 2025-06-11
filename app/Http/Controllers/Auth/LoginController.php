<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        DB::beginTransaction();

        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_BAD_REQUEST,
                'message' => 'email or password wrong!'
            ], MessageDakama::HTTP_BAD_REQUEST);
        }

        // if (!Hash::check($request->password, $user->password)) {
        //     return MessageDakama::render([
        //         'status' => MessageDakama::WARNING,
        //         'status_code' => MessageDakama::HTTP_BAD_REQUEST,
        //         'message' => 'email or password wrong!'
        //     ], MessageDakama::HTTP_BAD_REQUEST);
        // }

         if ((int) $user->status === User::TIDAK_AKTIF) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_FORBIDDEN,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'
            ], MessageDakama::HTTP_FORBIDDEN);
        }

        try {
            $role = [strtolower($user->role->name)];
            $token = $user->createToken('api', $role)->plainTextToken;

            DB::commit();
            return MessageDakama::render([
                "id" => $user->id,
                "role_id" => $user->role_id,
                "name" => $user->name,
                "email" => $user->email,
                "email_verified_at" => $user->email_verified_at,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at,
                'secret' => $token,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
