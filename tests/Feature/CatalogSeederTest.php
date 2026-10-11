<?php

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Database\Seeders\CarBrandsSeeder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(Brand::LOGO_DISK);
});

test('it links products to a category, brand, vehicles and tags from their names', function () {
    $pads = Product::factory()->create(['name' => 'تيل خلفي كورولا 2015 (لقم)']);
    $mirror = Product::factory()->create(['name' => 'كف مرايا صني 2012-2015 (يمين)']);
    $hose = Product::factory()->create(['name' => 'خرطوم ريداتير لانسر 2018 - سيفيك 2018', 'category' => 'oem']);
    $korean = Product::factory()->create(['name' => 'كف مرايا النترا 2021 CN7 (يمين)']);
    $plain = Product::factory()->create(['name' => '22-44-55']);

    $this->seed(CatalogSeeder::class);

    expect($pads->categories->pluck('name')->all())->toBe(['الفرامل'])
        ->and($pads->brands->pluck('name')->all())->toBe(['Toyota'])
        ->and($pads->fitments->map(fn ($fitment) => [$fitment->carModel->name, $fitment->year_from, $fitment->year_to])->all())->toBe([['Corolla', 2015, 2015]]);

    expect($mirror->categories->pluck('name')->all())->toBe(['المرايات'])
        ->and($mirror->fitments->map(fn ($fitment) => [$fitment->carModel->name, $fitment->year_from, $fitment->year_to])->all())->toBe([['Sunny', 2012, 2015]]);

    // Two vehicles of two brands; Daftra's "oem" category means original.
    expect($hose->categories->pluck('name')->all())->toBe(['التكييف والتبريد'])
        ->and($hose->brands->pluck('name')->all())->toBe(['Honda', 'Mitsubishi'])
        ->and($hose->fitments)->toHaveCount(2)
        ->and($hose->tags->pluck('name')->all())->toBe(['أصلي']);

    // Korean generation code CN7 = Elantra 2021 onwards.
    expect($korean->brands->pluck('name')->all())->toBe(['Hyundai'])
        ->and($korean->fitments->map(fn ($fitment) => [$fitment->carModel->name, $fitment->year_from, $fitment->year_to])->all())->toBe([['Elantra', 2021, null]]);

    // No known part word: "Other", and no vehicle.
    expect($plain->categories->pluck('name')->all())->toBe([CatalogSeeder::OTHER_CATEGORY])
        ->and($plain->fitments)->toBeEmpty();
});

test('generic words are only used when no part word matches', function () {
    $brakeHose = Product::factory()->create(['name' => 'خرطوم فرامل امامي سيراتو 2015']);
    $hose = Product::factory()->create(['name' => 'خرطوم صغير توسان 2018']);

    $this->seed(CatalogSeeder::class);

    expect($brakeHose->categories->pluck('name')->all())->toBe(['الفرامل'])
        ->and($hose->categories->pluck('name')->all())->toBe(['التكييف والتبريد']);
});

test('it creates the Japanese and Korean brands, their models, categories and tags', function () {
    $this->seed(CatalogSeeder::class);

    expect(Brand::count())->toBe(count(CatalogSeeder::BRAND_WORDS))
        ->and(Brand::pluck('name')->all())->toContain(...array_keys(CarBrandsSeeder::BRANDS), ...['Hyundai', 'Kia'])
        ->and(Brand::where('name', 'Toyota')->value('logo_path'))->not->toBeNull()
        ->and(Brand::where('name', 'Hyundai')->value('logo_path'))->not->toBeNull()
        ->and(CarModel::where('name', 'Sportage')->first()->brand->name)->toBe('Kia')
        ->and(CarModel::where('name', 'Corolla')->first()->brand->name)->toBe('Toyota')
        ->and(Category::where('name', 'المرايات')->exists())->toBeTrue()
        ->and(Tag::where('name', 'تجاري كوري')->value('color'))->toBe('amber');
});

test('it can run again without duplicating anything or removing admin changes', function () {
    $product = Product::factory()->create(['name' => 'فانوس خلفي كامري 2010 (يمين)']);
    $this->seed(CatalogSeeder::class);

    $custom = Category::factory()->create(['name' => 'Offers']);
    $product->categories()->attach($custom);

    $this->seed(CatalogSeeder::class);

    expect(Brand::where('name', 'Toyota')->count())->toBe(1)
        ->and($product->fitments()->count())->toBe(1)
        ->and($product->categories()->pluck('name')->all())->toContain('Offers', 'الإضاءة');
});
