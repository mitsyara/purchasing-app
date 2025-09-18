<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Port::withoutEvents(function () {
            $vnm_id = \App\Models\Country::whereAlpha3('VNM')->first()?->id;
            \App\Models\Port::create([
                'port_code' => 'SP-HP',
                'port_name' => 'Cảng Hải Phòng',
                'port_address' => '1P Lê Thánh Tông, Máy Tơ, Ngô Quyền, Hải Phòng',
                'country_id' => $vnm_id,
                'region' => \App\Enums\RegionEnum::North->value,
                'port_type' => \App\Enums\PortTypeEnum::Sea->value,
                'phones' => [
                    '0225 385 9945',
                    '0225 365 2192',
                ],
                'emails' => [
                    'haiphongport@haiphongport.com.vn',
                ],
                'website' => 'https://haiphongport.com.vn/',
            ]);
            \App\Models\Port::create([
                'port_code' => 'SP-CL',
                'port_name' => 'Cảng Cát Lái',
                'port_address' => 'Nguyễn Thị Định, Cát Lái, Quận 2, TP. Hồ Chí Minh',
                'country_id' => $vnm_id,
                'region' => \App\Enums\RegionEnum::South->value,
                'port_type' => \App\Enums\PortTypeEnum::Sea->value,
                'phones' => [
                    '1800 1188',
                    '0907 400 400',
                ],
                'emails' => [
                    'marketing@saigonnewport.com.vn',
                ],
                'website' => 'http://catlaiport.com.vn/pages/default.aspx',
            ]);
            \App\Models\Port::create([
                'port_code' => 'AP-NB',
                'port_name' => 'Sân bay Nội Bài',
                'port_address' => 'Xã Phú Minh, Huyện Sóc Sơn, Thành phố Hà Nội',
                'country_id' => $vnm_id,
                'region' => \App\Enums\RegionEnum::North->value,
                'port_type' => \App\Enums\PortTypeEnum::Air->value,
                'phones' => [
                    '1900 636 535',
                    '024 3886 6538',
                    '0389 166 566',
                ],
                'emails' => [
                    'vanthu.han@acv.vn',
                    'information.noibaiairport@gmail.com',
                ],
                'website' => 'https://www.noibaiairport.vn/',
            ]);
            \App\Models\Port::create([
                'port_code' => 'AP-TSN',
                'port_name' => 'Sân bay Tân Sơn Nhất',
                'port_address' => 'Đường Trường Sơn, Phường 2, Quận Tân Bình, Thành phố Hồ Chí Minh',
                'country_id' => $vnm_id,
                'region' => \App\Enums\RegionEnum::South->value,
                'port_type' => \App\Enums\PortTypeEnum::Air->value,
                'phones' => [
                    '0283 848 5383',
                ],
                'emails' => [
                    'feedback.sgn@acv.vn',
                ],
                'website' => 'https://www.vietnamairport.vn/tansonnhatairport/',
            ]);

            $this->command->info('Ports table seeded!');
        });
    }
}
