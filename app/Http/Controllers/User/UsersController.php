<?php

namespace App\Http\Controllers\User;

use App\Models\Role;
use App\Models\User;
use App\Mail\RegisterMail;
use App\Mail\SendTokenMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use App\Mail\ResetPasswordMail;
use App\Mail\PasswordRecoveryMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\User\CreateRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\Users\UsersCollection;
use App\Http\Requests\User\CreateNotLoginRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdatePassswordRequest;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan parameter 'search'
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('role', function ($query) use ($request) {
                        $query->where('role_name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('divisi', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter berdasarkan 'divisi_name' secara spesifik
        if ($request->has('divisi_name')) {
            $query->whereHas('divisi', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->divisi_name . '%');
            });
        }

        if ($request->has('role_id')) {
            // Ambil array role_id dari request, pastikan dalam bentuk array
            $roleIds = is_array($request->role_id) ? $request->role_id : explode(',', $request->role_id);
    
            // Terapkan filter berdasarkan role_id
            $query->whereIn('role_id', $roleIds);
        }

        // Filter berdasarkan rentang tanggal (parameter 'date')
        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);
            $query->whereBetween('created_at', $date);
        }

        $users = $query->paginate($request->per_page);

        return new UsersCollection($users);
    }

    public function usersAll(Request $request)
    {
        $query = User::query();

        $users = $query->get();

        return new UsersCollection($users);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return MessageDakama::notFound('User not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [
                    'id' => $user->role->id,
                    'role_name' => $user->role->role_name,
                ],
                'divisi' => [
                    'id' => $user->divisi->id ?? null,
                    'name' => $user->divisi->name ?? null,
                    'kode_divisi' => $user->divisi->kode_divisi ?? null,
                ],
                 'daily_salary' => $user->salary ? $user->salary->daily_salary : 0,
                'hourly_salary' => $user->salary ? $user->salary->hourly_salary : 0,
                'hourly_overtime_salary' => $user->salary ? $user->salary->hourly_overtime_salary : 0,
                'transport' => $user->salary ? $user->salary->transport : 0,
                'makan' => $user->salary ? $user->salary->makan : 0,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $currentUser = auth()->user();

            // Cek apakah role yang ditambahkan adalah OWNER
            if ($request->role == Role::OWNER) {
                // Hanya OWNER yang boleh menambahkan role OWNER
                if (!$currentUser->hasRole(Role::OWNER)) {
                    return MessageDakama::error('Only an OWNER can assign the OWNER role.');
                }
            }
    

            // Generate password acak 6 karakter
            $randomPassword = $this->generateRandomPassword();

            // Buat user baru dengan nama, email, password, role, dan divisi
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($randomPassword), // Enkripsi password acak
                'role_id' => $request->role,
                'divisi_id' => $request->divisi,
            ]);

            $user->salary()->create([
                "daily_salary" => $request->daily_salary,
                "hourly_salary" => $request->hourly_salary,
                "hourly_overtime_salary" => $request->hourly_overtime_salary,
                "transport" => $request->transport,
                "makan" => $request->makan,
            ]);

            $user->passwordRecovery = $randomPassword; // Simpan password acak sementara

            // Kirim email hanya jika bukan TENAGA_KERJA
            if ($request->role != Role::KARYAWAN) {
                Mail::to($user->email)->send(new RegisterMail($user));
            }

            DB::commit();

            // Tambahkan info password acak ke pesan sukses
            return MessageDakama::success("User {$user->name} has been successfully created with role {$user->role->role_name}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        if (!$user) {
            return MessageDakama::notFound('Data not found!');
        }

        try {
            $currentUser = auth()->user();
            $userData = [];

            // Update bidang-bidang yang disertakan dalam permintaan
            if ($request->has('name')) {
                $userData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $userData['email'] = $request->email;
            }
            if ($request->has('role')) {
                // Validasi untuk role OWNER
                if ($request->role == Role::OWNER) {
                    if (!$currentUser->hasRole(Role::OWNER)) {
                        return MessageDakama::error('Only an OWNER can assign the OWNER role.');
                    }
                }
                $userData['role_id'] = $request->role;
            }
            if ($request->has('divisi')) { // Tambahkan pengecekan untuk update divisi
                $userData['divisi_id'] = $request->divisi;
            }

            $user->update($userData);
            
            if ($user->salary ) {
                $user->salary->update([
                    "daily_salary" => $request->daily_salary,
                    "hourly_salary" => $request->hourly_salary,
                    "hourly_overtime_salary" => $request->hourly_overtime_salary,
                    "transport" => $request->transport,
                    "makan" => $request->makan,
                ]);
            }else {
                $user->salary()->create([
                    "daily_salary" => $request->daily_salary,
                    "hourly_salary" => $request->hourly_salary,
                    "hourly_overtime_salary" => $request->hourly_overtime_salary,
                    "transport" => $request->transport,
                    "makan" => $request->makan,
                ]);
            }

           /*  
            if ($user->manPower) {
                    $user->manPower->update([
                        "daily_salary_master" => $request->daily_salary,
                        "hourly_salary_master" => $request->hourly_salary,
                        "hourly_overtime_salary_master" => $request->hourly_overtime_salary,
                    ]);
                } 
            */       

            DB::commit();
            return MessageDakama::success("User $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    private function generateRandomPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = substr(str_shuffle($characters), 0, 6);
        return $password;
    }

    public function updatepassword(UpdatePassswordRequest $request)
    {
        DB::beginTransaction();

        $user = User::findOrFail(auth()->user()->id);

        // Verifikasi apakah old_password cocok dengan kata sandi saat ini
        if (!Hash::check($request->old_password, $user->password)) {
            return MessageDakama::error("Old password does not match");
        }

        try {
            // Update password dengan password baru yang di-hash
            $user->update([
                "password" => Hash::make($request->new_password)
            ]);

            DB::commit();
            return MessageDakama::success("User $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function resetPassword(Request $request, $id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        if (!$user) {
            return MessageDakama::notFound("data not found!");
        }

        $password = Str::random(8);
        if ($request->has('password')) {
            $password = $request->password;
        }

        try {
            $user->update([
                "password" => Hash::make($password)
            ]);
            $user->passwordRecovery = $password;

            Mail::to($user)->send(new ResetPasswordMail($user));

            DB::commit();
            return MessageDakama::success("user $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        if (!$user) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $user->delete();

            DB::commit();
            return MessageDakama::success("user $user->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }


    /* USERS NOT LOGIN */
    public function storeNotLogin(CreateNotLoginRequest $request)
    {
        DB::beginTransaction();

        try {
            // Password default
            $defaultPassword = 'P4sw0rDakam@';

            // Buat user baru dengan nama, email, password, role, dan divisi
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($defaultPassword), // Enkripsi password default
                'role_id' => $request->role,
                'divisi_id' => $request->divisi,
            ]);

            // Buat data salary untuk user
            $user->salary()->create([
                "daily_salary" => $request->daily_salary,
                "hourly_salary" => $request->hourly_salary,
                "hourly_overtime_salary" => $request->hourly_overtime_salary,
                "transport" => $request->transport,
                "makan" => $request->makan,
            ]);

            DB::commit();

            // Pesan sukses tanpa pengiriman email
            return MessageDakama::success("User {$user->name} has been successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function cekToken(Request $request)
    {
        // Ambil token dari query parameter URL
        $token = $request->query('token'); // Ambil token dari query string, misalnya ?token=your_token_here

        // Pastikan token diberikan dalam request
        if (!$token) {
            return MessageDakama::error('Token wajib diisi.');
        }

        // Cek apakah ada user dengan token yang sesuai
        $user = User::where('token', $token)->first();

        if (!$user) {
            return MessageDakama::error('Token tidak ditemukan.');
        }

        return MessageDakama::success('Token sudah dikirimkan.', ['token' => $user->token]);
    }

    public function UpdatePasswordWithEmail(Request $request)
    {
        DB::beginTransaction();
    
        try {
            // Validasi email yang diberikan
            $request->validate([
                'email' => 'required|email|exists:users,email', // Pastikan email valid dan ada di database
            ]);
    
            // Ambil user berdasarkan email
            $user = User::where('email', $request->email)->first();
    
            if (!$user) {
                return MessageDakama::error('Email not found!'); // Jika tidak ada user dengan email ini
            }
    
            // Generate password baru secara acak
            $newPassword = $this->generateRandomPassword(); // 8 karakter, bisa disesuaikan
    
            // Update password pengguna dengan password baru
            $user->update([
                'password' => bcrypt($newPassword), // Enkripsi password
            ]);
    
            // Kirim email dengan password baru ke pengguna
            Mail::to($user->email)->send(new PasswordRecoveryMail($user, $newPassword));
    
            DB::commit();
    
            // Tambahkan password baru di response
            return MessageDakama::success('Password has been reset successfully. Check your email for the new password.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error('An error occurred while resetting the password: ' . $th->getMessage());
        }
    }

    public function UpdatePasswordWithEmailToken(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validasi email yang diberikan
            $request->validate([
                'email' => 'required|email|exists:users,email', // Pastikan email valid dan ada di database
            ]);

            // Ambil user berdasarkan email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return MessageDakama::error('Email not found!'); // Jika tidak ada user dengan email ini
            }

            // Generate token verifikasi yang unik
            $token = $this->generateRandomPassword(); // Generate token acak, panjang 32 karakter

            // Simpan token ke kolom 'token' pada tabel users
            $user->update([
                'token' => $token,
            ]);

            // Kirim email dengan token ke pengguna
            Mail::to($user->email)->send(new SendTokenMail($user, $token));

            DB::commit();

            return MessageDakama::success('Token has been sent to your email. Please check your email for verification.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error('An error occurred while sending the token: ' . $th->getMessage());
        }
    }

    public function verifyTokenAndUpdatePassword(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validasi input dari pengguna
            $request->validate([
                'token' => 'required|string|exists:users,token',  // Token harus ada di database
                'password_new' => 'required|min:8|confirmed',     // Password baru harus valid dan sesuai konfirmasi
                'password_new_confirmation' => 'required',         // Konfirmasi password harus diisi
            ]);

            // Ambil user berdasarkan token yang diberikan
            $user = User::where('token', $request->token)->first();

            // Jika token tidak valid atau tidak ada
            if (!$user) {
                return MessageDakama::error('Invalid or expired token.');
            }

            // Cek apakah password baru dan konfirmasi password sesuai
            if ($request->password_new !== $request->password_new_confirmation) {
                return MessageDakama::error('Password confirmation does not match.');
            }

            // Update password pengguna dengan mengenkripsi password baru
            $user->update([
                'password' => bcrypt($request->password_new),  // Enkripsi password baru menggunakan bcrypt
                'token' => null,                               // Hapus token setelah digunakan
            ]);

            DB::commit();

            // Kembalikan pesan sukses
            return MessageDakama::success('Your password has been successfully updated.');

        } catch (\Throwable $th) {
            DB::rollBack();
            // Jika terjadi error
            return MessageDakama::error('An error occurred while updating the password: ' . $th->getMessage());
        }
    }

    

}
