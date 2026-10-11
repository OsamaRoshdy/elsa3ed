<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Adds the Japanese and Korean car brands with their logos (logos/*.png next to this file,
 * from github.com/filippofilip95/car-logos-dataset).
 *
 * Safe to run again: existing brands are kept, and a logo is only added when the brand has none,
 * so logos uploaded in Admin → Brands are never replaced.
 *
 *   php artisan db:seed --class=CarBrandsSeeder
 */
class CarBrandsSeeder extends Seeder
{
    /**
     * Brand name => logo file in database/seeders/logos.
     *
     * @var array<string, string>
     */
    public const BRANDS = [
        // Korean
        'Hyundai' => 'hyundai.png',
        'Kia' => 'kia.png',
        'SsangYong' => 'ssangyong.png',
        'Daewoo' => 'daewoo.png',
        // Japanese
        'Toyota' => 'toyota.png',
        'Nissan' => 'nissan.png',
        'Honda' => 'honda.png',
        'Mazda' => 'mazda.png',
        'Mitsubishi' => 'mitsubishi.png',
        'Subaru' => 'subaru.png',
        'Suzuki' => 'suzuki.png',
        'Lexus' => 'lexus.png',
        'Infiniti' => 'infiniti.png',
        'Acura' => 'acura.png',
        'Daihatsu' => 'daihatsu.png',
        'Isuzu' => 'isuzu.png',
        'Datsun' => 'datsun.png',
    ];

    /**
     * Seed the car brands.
     */
    public function run(): void
    {
        foreach (self::BRANDS as $name => $logo) {
            $brand = Brand::firstOrCreate(['name' => $name]);

            if ($brand->logo_path && Storage::disk(Brand::LOGO_DISK)->exists($brand->logo_path)) {
                continue;
            }

            $brand->update([
                'logo_path' => Storage::disk(Brand::LOGO_DISK)->putFileAs('brands', new File(__DIR__.'/logos/'.$logo), $logo),
            ]);
        }

        $this->command?->info(count(self::BRANDS).' car brands ready.');
    }
}
