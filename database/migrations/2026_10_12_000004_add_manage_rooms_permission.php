<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('name', 'manage_rooms')->exists();
        if (! $exists) {
            $id = DB::table('permissions')->insertGetId([
                'name' => 'manage_rooms',
                'display_name' => 'Kelola Informasi Kamar',
                'description' => 'Mengelola ruangan, perangkat display, dan penempatan pasien',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $admin = DB::table('roles')->where('name', 'admin')->first();
            if ($admin) {
                DB::table('role_permission')->insert([
                    'role_id' => $admin->id,
                    'permission_id' => $id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'manage_rooms')->first();
        if ($perm) {
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
