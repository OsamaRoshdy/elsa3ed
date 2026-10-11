<?php

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('home page shows categories, latest parts and brands', function () {
    $mirrors = Category::factory()->create(['name' => 'Mirrors']);
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $product = Product::factory()->create(['name' => 'Side mirror glass', 'image' => 'https://example.com/mirror.jpg']);
    $product->categories()->attach($mirrors);
    $product->brands()->attach($kia);
    Product::factory()->create(['name' => 'Photo-less part', 'image' => null]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mirrors')
        ->assertSee('Side mirror glass')
        ->assertDontSee('Photo-less part')
        ->assertSee(route('products.index', ['brand' => $kia->id]), false);
});

test('admins are sent from the store home to the admin dashboard', function () {
    $this->actingAs(admin())->get(route('home'))->assertRedirect(route('admin.dashboard'));
});

test('clients get the store layout with their account menu', function () {
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->actingAs($client)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('orders.index'), false)
        ->assertSee(route('logout'), false)
        ->assertDontSee(route('admin.dashboard'), false);
});

test('selecting a car remembers it and shows the parts that fit', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $cerato = CarModel::factory()->for($kia)->create(['name' => 'Cerato']);

    $this->post(route('vehicle.store'), ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2016])
        ->assertRedirect(route('products.index', ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2016]))
        ->assertSessionHas('vehicle', ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2016]);

    $this->get(route('home'))->assertSee('Kia Cerato 2016');

    $this->delete(route('vehicle.destroy'))->assertSessionMissing('vehicle');
});

test('the selected model must belong to the selected brand', function () {
    $kia = Brand::factory()->create();
    $otherModel = CarModel::factory()->create();

    $this->post(route('vehicle.store'), ['brand' => $kia->id, 'model' => $otherModel->id])
        ->assertSessionHasErrors('model');
});

test('product page tells whether the part fits the selected car', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $cerato = CarModel::factory()->for($kia)->create(['name' => 'Cerato']);
    $product = Product::factory()->create();
    $product->fitments()->create(['car_model_id' => $cerato->id, 'year_from' => 2015, 'year_to' => 2017]);

    $this->from(route('products.show', $product))
        ->post(route('vehicle.store'), ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2016, 'stay' => 1])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('products.show', $product))->assertSee(__('Fits your :car', ['car' => 'Kia Cerato 2016']));

    $this->post(route('vehicle.store'), ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2020, 'stay' => 1]);

    $this->get(route('products.show', $product))->assertSee(__('Does not fit your :car', ['car' => 'Kia Cerato 2020']));
});

test('catalogue can show available products only', function () {
    Product::factory()->create(['name' => 'In stock pump', 'stock_quantity' => 3]);
    Product::factory()->create(['name' => 'Untracked pump', 'stock_quantity' => null]);
    Product::factory()->create(['name' => 'Sold out pump', 'stock_quantity' => 0]);

    $this->get(route('products.index', ['stock' => 'in_stock']))
        ->assertOk()
        ->assertSee('In stock pump')
        ->assertSee('Untracked pump')
        ->assertDontSee('Sold out pump');
});

test('admin can upload and remove a category image', function () {
    Storage::fake(Category::IMAGE_DISK);
    $admin = admin();

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Lighting',
        'image' => UploadedFile::fake()->image('lighting.png'),
    ]);

    $category = Category::sole();
    Storage::disk(Category::IMAGE_DISK)->assertExists($category->image_path);
    $path = $category->image_path;

    $this->actingAs($admin)->put(route('admin.categories.update', $category), ['name' => 'Lighting', 'remove_image' => '1']);

    expect($category->fresh()->image_path)->toBeNull();
    Storage::disk(Category::IMAGE_DISK)->assertMissing($path);
});

test('home page lists every brand, the ones with the most parts first', function () {
    $toyota = Brand::factory()->create(['name' => 'Toyota']);
    $kia = Brand::factory()->create(['name' => 'Kia']);
    Product::factory()->count(2)->create()->each(fn ($product) => $product->brands()->attach($kia));

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder([route('products.index', ['brand' => $kia->id]), route('products.index', ['brand' => $toyota->id])], false)
        ->assertSee(__('Parts for :count car brands', ['count' => 2]));
});

test('catalogue sidebar lists the brands that have products and filters by brand', function () {
    $toyota = Brand::factory()->create(['name' => 'Toyota']);
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $isuzu = Brand::factory()->create(['name' => 'Isuzu']);
    Product::factory()->create(['name' => 'Corolla mirror'])->brands()->attach($toyota);
    Product::factory()->create(['name' => 'Sportage lamp'])->brands()->attach($kia);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('name="brand" value="'.$toyota->id.'"', false)
        ->assertDontSee('name="brand" value="'.$isuzu->id.'"', false);

    $this->get(route('products.index', ['brand' => $toyota->id]))
        ->assertSee('Corolla mirror')
        ->assertDontSee('Sportage lamp');
});

test('a model from another brand is ignored', function () {
    $toyota = Brand::factory()->create(['name' => 'Toyota']);
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $sportage = CarModel::factory()->for($kia)->create(['name' => 'Sportage']);
    Product::factory()->create(['name' => 'Corolla mirror'])->brands()->attach($toyota);

    $this->get(route('products.index', ['brand' => $toyota->id, 'model' => $sportage->id]))
        ->assertOk()
        ->assertSee('Corolla mirror');
});
