<?php

namespace Database\Seeders;

use App\Enums\FuelType;
use App\Models\VehiclePrice;
use Illuminate\Database\Seeder;

class VehiclePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect($this->seedRows())->each(function (array $row): void {
            $newPrice = $row['new_price'];
            $resale36m = $row['resale_36m'];

            VehiclePrice::query()->updateOrCreate(
                [
                    'manufacturer' => $row['manufacturer'],
                    'model' => $row['model'],
                    'trim' => $row['trim'],
                    'fuel_type' => $row['fuel_type'],
                    'model_year' => 2026,
                ],
                [
                    'new_price' => $newPrice,
                    'registration_cost' => (int) round($newPrice * 0.07),
                    'resale_12m' => (int) round($newPrice * 0.84),
                    'resale_24m' => (int) round($newPrice * 0.74),
                    'resale_36m' => $resale36m,
                    'resale_48m' => (int) round($resale36m * 0.88),
                    'insurance_annual' => max(900000, (int) round($newPrice * 0.025, -4)),
                    'source' => 'manual_seed_2026',
                ],
            );
        });
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    protected function seedRows(): array
    {
        return [
            ['manufacturer' => '현대', 'model' => '그랜저 2.5', 'trim' => '프리미엄', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 37980000, 'resale_36m' => 25000000],
            ['manufacturer' => '현대', 'model' => '그랜저 2.5', 'trim' => '익스클루시브', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 42870000, 'resale_36m' => 28000000],
            ['manufacturer' => '현대', 'model' => '그랜저 2.5', 'trim' => '아너스', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 45130000, 'resale_36m' => 29500000],
            ['manufacturer' => '현대', 'model' => '그랜저 2.5', 'trim' => '캘리그래피', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 47100000, 'resale_36m' => 31000000],
            ['manufacturer' => '현대', 'model' => '그랜저 3.5', 'trim' => '프리미엄', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 40420000, 'resale_36m' => 26500000],
            ['manufacturer' => '현대', 'model' => '소나타 2.0', 'trim' => '프리미엄', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 29430000, 'resale_36m' => 18500000],
            ['manufacturer' => '현대', 'model' => '소나타 HEV', 'trim' => '프리미엄', 'fuel_type' => FuelType::Hybrid->value, 'new_price' => 32870000, 'resale_36m' => 22000000],
            ['manufacturer' => '현대', 'model' => '카니발 3.5', 'trim' => '노블레스', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 42820000, 'resale_36m' => 30000000],
            ['manufacturer' => '현대', 'model' => '카니발 3.5', 'trim' => '시그니처', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 47210000, 'resale_36m' => 33000000],
            ['manufacturer' => '현대', 'model' => '스타리아', 'trim' => '투어러 9인승', 'fuel_type' => FuelType::Diesel->value, 'new_price' => 37750000, 'resale_36m' => 25000000],
            ['manufacturer' => '현대', 'model' => '팰리세이드 3.8', 'trim' => '프레스티지', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 43380000, 'resale_36m' => 31000000],
            ['manufacturer' => '현대', 'model' => '아이오닉5', 'trim' => '롱레인지', 'fuel_type' => FuelType::Electric->value, 'new_price' => 52000000, 'resale_36m' => 28000000],
            ['manufacturer' => '기아', 'model' => 'K5 2.0', 'trim' => '프레스티지', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 29400000, 'resale_36m' => 18000000],
            ['manufacturer' => '기아', 'model' => 'K8 2.5', 'trim' => '노블레스', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 39500000, 'resale_36m' => 26000000],
            ['manufacturer' => '기아', 'model' => '쏘렌토 2.2D', 'trim' => '프레스티지', 'fuel_type' => FuelType::Diesel->value, 'new_price' => 38650000, 'resale_36m' => 26000000],
            ['manufacturer' => '기아', 'model' => '카니발 3.5', 'trim' => '노블레스', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 42820000, 'resale_36m' => 30000000],
            ['manufacturer' => '기아', 'model' => '스포티지 1.6T', 'trim' => '프레스티지', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 31400000, 'resale_36m' => 20000000],
            ['manufacturer' => '기아', 'model' => 'EV6', 'trim' => '롱레인지', 'fuel_type' => FuelType::Electric->value, 'new_price' => 53900000, 'resale_36m' => 27000000],
            ['manufacturer' => '기아', 'model' => 'EV9', 'trim' => '에어 6인승', 'fuel_type' => FuelType::Electric->value, 'new_price' => 73860000, 'resale_36m' => 38000000],
            ['manufacturer' => '기아', 'model' => '레이', 'trim' => '프레스티지', 'fuel_type' => FuelType::Gasoline->value, 'new_price' => 16150000, 'resale_36m' => 11000000],
        ];
    }
}
