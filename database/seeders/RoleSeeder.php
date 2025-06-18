<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            "Policy",
            "Organization Design",
            "PKB",
            "Guidebook",
            "HRSI",
            "BTR Training Guide",
            "Guidebook Employees",
            "Guidebook Manager",
            "HRSI Bantuan Karyawan",
            "HRSI Cuti Karyawan",
            "HRSI Fasilitas Kesehatan",
            "HRSI Fasilitas Nomor Operasional",
            "HRSI Insentif",
            "HRSI Kegiatan Politik Karyawan",
            "HRSI Ketentuan Ekspatriat",
            "HRSI Mutasi Wilayah",
            "HRSI Organization Development",
            "HRSI Pajak Karyawan Iuran Jaminan Sosial",
            "HRSI Pedoman Kemitraan",
            "HRSI Pemutusan Hubungan Kerja",
            "HRSI Penghargaan Karyawan",
            "HRSI Perjalanan Dinas",
            "HRSI Pernyataan Tahunan",
            "HRSI Sistem Pengupahan",
            "HRSI Talent Management",
            "HRSI Tata Cara Karyawan Bekerja",
            "HRSI Tunjangan Penugasan Tertentu",
            "HRSI Workforce Planning",
            "Response Tuning",
            "Create Response Tuning",
            "Delete Response Tuning",
            "Forms",
            "Create Form",
            "Dashboard",
            "Settings",
            "Theme And Language",
            "Edit Form",
            "Delete Form",
            "Astrid Chat",
            "Export Response Tuning",
            "Search Response Tuning",
            "Domain Data",
            "Action",
            "Policy",
            "Organization Design",
            "PKB",
            "Guidebook",
            "HRSI",
            "BTR Training Guide",
            "Guidebook Employees",
            "Guidebook Manager",
            "HRSI Bantuan Karyawan",
            "HRSI Cuti Karyawan",
            "HRSI Fasilitas Kesehatan",
            "HRSI Fasilitas Nomor Operasional",
            "HRSI Insentif",
            "HRSI Kegiatan Politik Karyawan",
            "HRSI Ketentuan Ekspatriat",
            "HRSI Mutasi Wilayah",
            "HRSI Organization Development",
            "HRSI Pajak Karyawan Iuran Jaminan Sosial",
            "HRSI Pedoman Kemitraan",
            "HRSI Pemutusan Hubungan Kerja",
            "HRSI Penghargaan Karyawan",
            "HRSI Perjalanan Dinas",
            "HRSI Pernyataan Tahunan",
            "HRSI Sistem Pengupahan",
            "HRSI Talent Management",
            "HRSI Tata Cara Karyawan Bekerja",
            "HRSI Tunjangan Penugasan Tertentu",
            "HRSI Workforce Planning",
            "Response Tuning",
            "Create Response Tuning",
            "Delete Response Tuning",
            "Forms",
            "Create Form",
            "Dashboard",
            "Settings",
            "Users",
            "Permissions",
            "Theme And Language",
            "Edit Form",
            "Delete Form",
            "Roles",
            "Create User",
            "Edit User",
            "Delete User",
            "Create Permission",
            "Delete Permission",
            "Edit Permission",
            "Create Role",
            "Edit Role",
            "Delete Role",
            "Astrid Chat",
            "Export Response Tuning",
            "Search Response Tuning",
            "Import Response Tuning",
            "Unanswered Questions",
            "Reset 2FA User"
        ];

        $access[Role::OWNER] = $permissions;

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $roles = Role::all();

        foreach ($roles as $role) {
            if (isset($access[$role->id])) {
                $perms = Permission::whereIn('name', $access[$role->id])->pluck('name');

                $role->syncPermissions($perms);
            }

            $user = User::where('role_id', $role->id)->first();
            $user->assignRole($role);
        }
    }
}
