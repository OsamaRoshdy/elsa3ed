<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create, rename and delete a category', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Mirrors'])
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::sole();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), ['name' => 'Side mirrors'])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->name)->toBe('Side mirrors');

    $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('Side mirrors');

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::count())->toBe(0);
});

test('category names must be unique', function () {
    Category::factory()->create(['name' => 'Doors']);

    $this->actingAs(admin())
        ->post(route('admin.categories.store'), ['name' => 'Doors'])
        ->assertSessionHasErrors('name');
});

test('clients cannot manage categories', function () {
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->actingAs($client)->get(route('admin.categories.index'))->assertForbidden();
    $this->actingAs($client)->post(route('admin.categories.store'), ['name' => 'Engine'])->assertForbidden();
});

test('admin can assign several categories and brands to a product at once', function () {
    $product = Product::factory()->create();
    [$mirrors, $doors] = Category::factory()->createMany([['name' => 'Mirrors'], ['name' => 'Doors']]);
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee(['Mirrors', 'Kia']);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), ['categories' => [$mirrors->id, $doors->id], 'brands' => [$kia->id]])
        ->assertRedirect(route('admin.products.index'));

    expect($product->categories()->pluck('name')->all())->toBe(['Doors', 'Mirrors'])
        ->and($product->brands()->pluck('name')->all())->toBe(['Kia']);

    $this->actingAs($admin)->put(route('admin.products.update', $product), ['brands' => [$kia->id]]);

    expect($product->categories()->count())->toBe(0)
        ->and($product->brands()->count())->toBe(1);
});

test('assigning an unknown category is rejected', function () {
    $this->actingAs(admin())
        ->put(route('admin.products.update', Product::factory()->create()), ['categories' => [999]])
        ->assertSessionHasErrors('categories.0');
});

test('deleting a category keeps its products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category);

    $this->actingAs(admin())->delete(route('admin.categories.destroy', $category));

    expect($product->fresh())->not->toBeNull()
        ->and($product->categories()->count())->toBe(0);
});

test('catalogue filters by local category, not the Daftra category', function () {
    $mirrors = Category::factory()->create(['name' => 'Mirrors']);
    $engine = Category::factory()->create(['name' => 'Engine']);

    Product::factory()->create(['name' => 'Left door mirror', 'category' => 'oem'])->categories()->attach($mirrors);
    Product::factory()->create(['name' => 'Oil pump'])->categories()->attach($engine);

    $this->get(route('products.index', ['category' => $mirrors->id]))
        ->assertOk()
        ->assertSee('Left door mirror')
        ->assertDontSee('Oil pump');

    $this->get(route('products.index'))
        ->assertSeeInOrder(['name="category" value="'.$engine->id.'"', 'name="category" value="'.$mirrors->id.'"'], false)
        ->assertDontSee('>oem<', false);
});

test('catalogue category filter lists only categories with active products', function () {
    Category::factory()->create(['name' => 'Empty category']);
    Product::factory()->inactive()->create()->categories()->attach(Category::factory()->create(['name' => 'Hidden category']));

    $this->get(route('products.index'))
        ->assertDontSee('Empty category')
        ->assertDontSee('Hidden category');
});

test('admin product list can be filtered by category', function () {
    $doors = Category::factory()->create(['name' => 'Doors']);
    Product::factory()->create(['name' => 'Rear door handle'])->categories()->attach($doors);
    Product::factory()->create(['name' => 'Headlight bulb']);

    $this->actingAs(admin())
        ->get(route('admin.products.index', ['category' => $doors->id]))
        ->assertOk()
        ->assertSee('Rear door handle')
        ->assertDontSee('Headlight bulb');
});

test('product page shows related products from the same categories', function () {
    $ac = Category::factory()->create(['name' => 'A/C']);
    $product = Product::factory()->create(['name' => 'A/C compressor']);
    $product->categories()->attach($ac);
    Product::factory()->create(['name' => 'A/C condenser'])->categories()->attach($ac);
    Product::factory()->create(['name' => 'Brake disc']);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee(__('More in :category', ['category' => 'A/C']))
        ->assertSee('A/C condenser')
        ->assertDontSee('Brake disc');
});
