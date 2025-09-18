<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::withoutEvents(function (): void {
            // Super Administrator
            \App\Models\User::create([
                'name' => 'Super Administrator',
                'phone' => '0916737190',
                'email' => 'mitsyara@gmail.com',
                'password' => bcrypt('@nhthaiHung1'),
                'status' => \App\Enums\UserStatusEnum::Active->value,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // name, phone, email
            $users = [
                ['Lê Hải Anh', '0944866447', 'purchasing02@vhl.com.vn'],
                ['Trần Trung Nghĩa', '0358461013', 'purchasing05@vhl.com.vn'],
                ['Nguyễn Trung Hiếu', '0966823297', 'purchasing@vhl.com.vn'],
                ['Tạ Quang Trường', '0383356563', 'pino@vhl.com.vn'],
                ['Lê Thị Thúy Hiền', '0356119128', 'logistics02@vhl.com.vn'],
                ['Nguyễn Thị Quyên', '0962214658', 'purchasing04@vhl.com.vn'],
                ['Cao Hà Thu', '0947725418', 'logistics01@vhl.com.vn'],
                ['Nguyễn Minh Thùy', '0375552297', 'logistics04@vhl.com.vn'],
                ['Đỗ Cao Hải Ninh', '0906268911', 'logistics03@vhl.com.vn'],
                ['Trần Lan Anh', '0988891636', 'anh.tl@vhl.com.vn'],
                ['Trần Thanh Tâm', '0334155359', 'tam.tt@vhl.com.vn'],
            ];
            foreach ($users as $user) {
                \App\Models\User::create([
                    'name' => $user[0],
                    'phone' => $user[1],
                    'email' => $user[2],
                    'password' => bcrypt('Vhl@2022'),
                    'status' => \App\Enums\UserStatusEnum::Active->value,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info('Users table seeded!');
        });
    }
}
