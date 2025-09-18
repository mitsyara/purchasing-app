<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Company::withoutEvents(function (): void {
            $vnm_id = \App\Models\Country::query()->where('alpha3', 'VNM')->first()?->id;

            $companies = [
                [
                    'company_code' => 'VHL',
                    'company_name' => 'CÔNG TY TNHH THƯƠNG MẠI QUỐC TẾ VHL',
                    'company_address' => 'Thôn 3, Xã Nam Phù, TP Hà Nội, Việt Nam',
                    'company_email' => 'info@vhl.com.vn',
                    'company_phone' => '0328772099',
                    'country_id' => $vnm_id,
                    'company_owner_gender' => \App\Enums\ContactGenderEnum::Mr->value,
                    'company_owner' => 'Đặng Khắc Mạnh',
                    'company_owner_title' => 'Giám đốc',
                    'company_website' => 'https://vhl.com.vn',
                    'company_tax_id' => '0110137014',
                    'company_bank_accounts' => [
                        '1482201040469 tại Ngân hàng Agribank, CN Hùng Vương',
                    ],
                ],

                [
                    'company_code' => 'GHC',
                    'company_name' => 'CÔNG TY CỔ PHẦN HÓA CHẤT GLOBAL HUB',
                    'company_address' => '93 Xô Viết Nghệ Tĩnh, Phường Gia Định, TP Hồ Chí Minh, Việt Nam',
                    'company_email' => 'info@globalhub.com.vn',
                    'company_phone' => '0904243505',
                    'country_id' => $vnm_id,
                    'company_owner_gender' => \App\Enums\ContactGenderEnum::Mr->value,
                    'company_owner' => 'Dholia Vipulkumar Bhanubhai',
                    'company_owner_title' => 'Giám đốc',
                    'company_website' => 'https://globalhub.com.vn',
                    'company_tax_id' => '0317768979',
                    'company_bank_accounts' => [],
                ],

                [
                    'company_code' => 'CAN',
                    'company_name' => 'CÔNG TY CỔ PHẦN XUẤT NHẬP KHẨU CAN VN',
                    'company_address' => 'Số 9 ngõ 17, đường An Dương, Phường Hồng Hà, TP Hà Nội, Việt Nam',
                    'company_email' => 'info@cangroup.vn',
                    'company_phone' => null,
                    'country_id' => $vnm_id,
                    'company_owner_gender' => \App\Enums\ContactGenderEnum::Mr->value,
                    'company_owner' => 'Phạm Thế Quang',
                    'company_owner_title' => 'Giám đốc',
                    'company_website' => 'https://cangroup.vn',
                    'company_tax_id' => '0105363018',
                    'company_bank_accounts' => [],
                ],

                [
                    'company_code' => 'VUT',
                    'company_name' => 'CÔNG TY CỔ PHẦN THƯƠNG MẠI VIỆT UY',
                    'company_address' => 'Số nhà 67 ngõ 82 phố Chùa Láng, Phường Láng Thượng, Quận Đống Đa, Thành phố Hà Nội, Việt Nam',
                    'company_email' => 'info@vietuy.vn',
                    'company_phone' => '0936486676',
                    'country_id' => $vnm_id,
                    'company_owner_gender' => \App\Enums\ContactGenderEnum::Mr->value,
                    'company_owner' => 'Bùi Đình Quỳnh',
                    'company_owner_title' => 'Giám đốc',
                    'company_website' => 'https://vietuy.vn',
                    'company_tax_id' => '0109528817',
                    'company_bank_accounts' => [
                        '6961116969999 tại Ngân hàng TMCP Quân đội',
                    ],
                ],
            ];
            foreach ($companies as $company) {
                \App\Models\Company::create($company);
            }

            $this->command->info('Company table seeded!');
        });
    }
}
