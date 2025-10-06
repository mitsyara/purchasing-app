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
                ["Lê Hải Anh", "0944866447", "susan@vietuy.vn"],
                ["Trần Mai Lan", "0932365666", "helen@globalhub.com.vn"],
                ["Đỗ Cao Hải Ninh", "0906268911", "ryan@globalhub.com.vn"],
                ["Trần Trung Nghĩa", "0358461013 ", "purchasing03@vhl.com.vn"],
                ["Nguyễn Ánh Nguyệt", "0338868227", "purchasing06@vhl.com.vn"],
                ["Nguyễn Thị Quyên", "0962214658", "purchasing04@vhl.com.vn"],
                ["Nguyễn Trung Hiếu", "0966823297", "purchasing01@vhl.com.vn"],
                ["Tạ Quang Trường", "0383356563", "pino@globalhub.com.vn"],
                ["Lê Thị Thúy Hiền", "0356119128", "leila@globalhub.com.vn"],
                ["Phạm Thanh Tuấn", "0931723338", "tuan.pt@vhl.com.vn"],
                ["Cao Hà Thu", "0947725418", "logistics04@vhl.com.vn"],
                ["Nguyễn Thị Minh Thùy", "0375552297", "logistic01@vhl.com.vn"],
                ["Trần Thúy Nga", "0395393474", "nina@globalhub.com.vn"],
                ["Lê Thái Bình", "0967887482", "august@globalhub.com.vn"],
                ["Chu Thục Trinh", "0387281624", "logistics03@vhl.com.vn"],
                ["Trịnh Hoài Anh", "0346932826 ", "logistics05@vhl.com.vn"],
                ["Nguyễn Thị Ngọc Linh", "0888765688", "logistics06@vhl.com.vn"],
                ["Nguyễn Thị Thanh Hà ", "0358362128", "ng.hannah@cangroup.vn"],
                ["Đặng Thị Phương Anh ", "0766575271", "d.maru@cangroup.vn"],
                ["Nguyễn Thị Hoa", "0988778766", "n.elise@cangroup.vn"],
                ["Trần Thanh Hằng", "0375746970", "tr.mia@cangroup.vn"],
                ["Nguyễn Thu Thủy ", "0349136999", "ng.maris@cangroup.vn"],
                ["Lương Thị Lành", "0363994290", "lg.julianna@cangroup.vn"],
                ["Nguyễn Hoàng Yến ", "0975333620", "ng.yen@cangroup.vn"],
                ["Nguyễn Thị Lệ Yến ", "0932209646", "n.lenka@cangroup.vn"],
                ["Nguyễn Thị Thu Hòa", "0915222380", "nancy@globalhub.com.vn"],
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
