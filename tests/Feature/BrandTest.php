<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin can create, rename and delete a brand', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.brands.store'), ['name' => 'Kia'])
        ->assertRedirect(route('admin.brands.index'));

    $brand = Brand::sole();
    expect($brand->name)->toBe('Kia');

    $this->actingAs($admin)
        ->put(route('admin.brands.update', $brand), ['name' => 'KIA Motors'])
        ->assertRedirect(route('admin.brands.index'));

    expect($brand->fresh()->name)->toBe('KIA Motors');

    $this->actingAs($admin)
        ->delete(route('admin.brands.destroy', $brand))
        ->assertRedirect(route('admin.brands.index'));

    expect(Brand::count())->toBe(0);
});

test('brand names must be unique', function () {
    Brand::factory()->create(['name' => 'Nissan']);

    $this->actingAs(admin())
        ->post(route('admin.brands.store'), ['name' => 'Nissan'])
        ->assertSessionHasErrors('name');
});

test('clients cannot manage brands', function () {
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->actingAs($client)->get(route('admin.brands.index'))->assertForbidden();
    $this->actingAs($client)->post(route('admin.brands.store'), ['name' => 'Kia'])->assertForbidden();
});

test('admin can assign several brands to a product and change them later', function () {
    $product = Product::factory()->create();
    [$kia, $nissan, $hyundai] = Brand::factory()->createMany([['name' => 'Kia'], ['name' => 'Nissan'], ['name' => 'Hyundai']]);
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Hyundai');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), ['brands' => [$kia->id, $nissan->id]])
        ->assertRedirect(route('admin.products.index'));

    expect($product->brands()->pluck('name')->all())->toBe(['Kia', 'Nissan']);

    $this->actingAs($admin)->put(route('admin.products.update', $product), ['brands' => [$hyundai->id]]);

    expect($product->brands()->pluck('name')->all())->toBe(['Hyundai']);

    $this->actingAs($admin)->put(route('admin.products.update', $product), []);

    expect($product->brands()->count())->toBe(0);
});

test('assigning an unknown brand is rejected', function () {
    $product = Product::factory()->create();

    $this->actingAs(admin())
        ->put(route('admin.products.update', $product), ['brands' => [999]])
        ->assertSessionHasErrors('brands.0');
});

test('deleting a brand keeps its products', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create();
    $product->brands()->attach($brand);

    $this->actingAs(admin())->delete(route('admin.brands.destroy', $brand));

    expect($product->fresh())->not->toBeNull()
        ->and($product->brands()->count())->toBe(0);
});

test('catalogue can be filtered by brand', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $nissan = Brand::factory()->create(['name' => 'Nissan']);

    $shared = Product::factory()->create(['name' => 'Shared oil filter']);
    $shared->brands()->attach([$kia->id, $nissan->id]);
    Product::factory()->create(['name' => 'Nissan only bumper'])->brands()->attach($nissan);
    Product::factory()->create(['name' => 'Unbranded wiper']);

    $this->get(route('products.index', ['brand' => $kia->id]))
        ->assertOk()
        ->assertSee('Shared oil filter')
        ->assertDontSee('Nissan only bumper')
        ->assertDontSee('Unbranded wiper');

    $this->get(route('products.index', ['brand' => $nissan->id]))
        ->assertSee('Shared oil filter')
        ->assertSee('Nissan only bumper')
        ->assertDontSee('Unbranded wiper');
});

test('admin product list can be filtered by brand', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    Product::factory()->create(['name' => 'Kia brake pad'])->brands()->attach($kia);
    Product::factory()->create(['name' => 'Generic bulb']);

    $this->actingAs(admin())
        ->get(route('admin.products.index', ['brand' => $kia->id]))
        ->assertOk()
        ->assertSee('Kia brake pad')
        ->assertDontSee('Generic bulb');
});

test('product page shows its brands', function () {
    $product = Product::factory()->create();
    $product->brands()->attach(Brand::factory()->createMany([['name' => 'Kia'], ['name' => 'Hyundai']]));

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSeeInOrder(['Hyundai', 'Kia']);
});

test('admin can upload, replace and remove a brand logo', function () {
    Storage::fake(Brand::LOGO_DISK);
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.brands.store'), ['name' => 'Kia', 'logo' => UploadedFile::fake()->image('kia.png', 200, 200)])
        ->assertRedirect(route('admin.brands.index'));

    $brand = Brand::sole();
    $firstLogo = $brand->logo_path;
    Storage::disk(Brand::LOGO_DISK)->assertExists($firstLogo);
    expect($brand->logo_url)->toEndWith($firstLogo);

    $this->actingAs($admin)
        ->put(route('admin.brands.update', $brand), ['name' => 'Kia', 'logo' => UploadedFile::fake()->image('kia-new.webp')]);

    $brand->refresh();
    Storage::disk(Brand::LOGO_DISK)->assertMissing($firstLogo);
    Storage::disk(Brand::LOGO_DISK)->assertExists($brand->logo_path);

    $secondLogo = $brand->logo_path;

    $this->actingAs($admin)
        ->put(route('admin.brands.update', $brand), ['name' => 'Kia', 'remove_logo' => '1']);

    expect($brand->fresh()->logo_path)->toBeNull();
    Storage::disk(Brand::LOGO_DISK)->assertMissing($secondLogo);
});

test('updating a brand without a new file keeps the logo', function () {
    Storage::fake(Brand::LOGO_DISK);
    $logo = UploadedFile::fake()->image('nissan.png')->store('brands', Brand::LOGO_DISK);
    $brand = Brand::factory()->create(['name' => 'Nissan', 'logo_path' => $logo]);

    $this->actingAs(admin())->put(route('admin.brands.update', $brand), ['name' => 'Nissan Motors']);

    expect($brand->fresh()->logo_path)->toBe($logo);
    Storage::disk(Brand::LOGO_DISK)->assertExists($logo);
});

test('deleting a brand deletes its logo file', function () {
    Storage::fake(Brand::LOGO_DISK);
    $logo = UploadedFile::fake()->image('hyundai.png')->store('brands', Brand::LOGO_DISK);
    $brand = Brand::factory()->create(['logo_path' => $logo]);

    $this->actingAs(admin())->delete(route('admin.brands.destroy', $brand));

    Storage::disk(Brand::LOGO_DISK)->assertMissing($logo);
});

test('brand logo must be a png, jpg or webp image', function () {
    Storage::fake(Brand::LOGO_DISK);

    $this->actingAs(admin())
        ->post(route('admin.brands.store'), ['name' => 'Kia', 'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])
        ->assertSessionHasErrors('logo');

    $this->actingAs(admin())
        ->post(route('admin.brands.store'), ['name' => 'Kia', 'logo' => UploadedFile::fake()->image('huge.png')->size(3000)])
        ->assertSessionHasErrors('logo');
});
