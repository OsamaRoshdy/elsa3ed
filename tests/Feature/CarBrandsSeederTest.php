<?php

use App\Models\Brand;
use Database\Seeders\CarBrandsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it adds the Japanese and Korean brands with their logos', function () {
    Storage::fake(Brand::LOGO_DISK);

    $this->seed(CarBrandsSeeder::class);

    expect(Brand::count())->toBe(count(CarBrandsSeeder::BRANDS));

    foreach (['Toyota' => 'toyota.png', 'Kia' => 'kia.png'] as $name => $file) {
        $brand = Brand::where('name', $name)->sole();
        Storage::disk(Brand::LOGO_DISK)->assertExists($brand->logo_path);
        expect($brand->logo_url)->toEndWith('brands/'.$file);
    }
});

test('it keeps existing brands and logos uploaded by the admin', function () {
    Storage::fake(Brand::LOGO_DISK);
    $customLogo = UploadedFile::fake()->image('my-nissan.png')->store('brands', Brand::LOGO_DISK);
    Brand::factory()->create(['name' => 'Nissan', 'logo_path' => $customLogo]);

    $this->seed(CarBrandsSeeder::class);
    $this->seed(CarBrandsSeeder::class);

    expect(Brand::where('name', 'Nissan')->count())->toBe(1)
        ->and(Brand::where('name', 'Nissan')->value('logo_path'))->toBe($customLogo);
});
