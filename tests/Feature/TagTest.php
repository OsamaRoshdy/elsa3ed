<?php

use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create, edit and delete a tag', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.tags.store'), ['name' => 'Original', 'color' => 'emerald'])
        ->assertRedirect(route('admin.tags.index'));

    $tag = Tag::sole();
    expect($tag->only(['name', 'color']))->toBe(['name' => 'Original', 'color' => 'emerald']);

    $this->actingAs($admin)->get(route('admin.tags.index'))->assertOk()->assertSee('Original');

    $this->actingAs($admin)
        ->put(route('admin.tags.update', $tag), ['name' => 'Genuine', 'color' => 'sky'])
        ->assertRedirect(route('admin.tags.index'));

    expect($tag->fresh()->only(['name', 'color']))->toBe(['name' => 'Genuine', 'color' => 'sky']);

    $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag));

    expect(Tag::count())->toBe(0);
});

test('tag names are unique and colors must come from the palette', function () {
    Tag::factory()->create(['name' => 'Korea aftermarket']);

    $this->actingAs(admin())
        ->post(route('admin.tags.store'), ['name' => 'Korea aftermarket', 'color' => 'neon'])
        ->assertSessionHasErrors(['name', 'color']);
});

test('clients cannot manage tags', function () {
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->actingAs($client)->get(route('admin.tags.index'))->assertForbidden();
});

test('admin can give a product several tags', function () {
    $product = Product::factory()->create();
    [$original, $korea] = Tag::factory()->createMany([['name' => 'Original'], ['name' => 'Korea aftermarket']]);
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Korea aftermarket');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), ['tags' => [$original->id, $korea->id]])
        ->assertSessionHasNoErrors();

    expect($product->tags()->pluck('name')->all())->toBe(['Korea aftermarket', 'Original']);

    $this->actingAs($admin)->put(route('admin.products.update', $product), ['tags' => [999]])
        ->assertSessionHasErrors('tags.0');
});

test('catalogue can be filtered by tag and shows tag badges', function () {
    $original = Tag::factory()->create(['name' => 'Original', 'color' => 'emerald']);
    $korea = Tag::factory()->create(['name' => 'Korea aftermarket']);

    Product::factory()->create(['name' => 'Genuine oil filter'])->tags()->attach($original);
    Product::factory()->create(['name' => 'Copy oil filter'])->tags()->attach($korea);

    $this->get(route('products.index', ['tag' => $original->id]))
        ->assertOk()
        ->assertSee('Genuine oil filter')
        ->assertDontSee('Copy oil filter')
        ->assertSee('bg-emerald-50', false);
});

test('admin product list can be filtered by tag', function () {
    $korea = Tag::factory()->create(['name' => 'Korea aftermarket']);
    Product::factory()->create(['name' => 'Aftermarket bumper'])->tags()->attach($korea);
    Product::factory()->create(['name' => 'Plain bumper']);

    $this->actingAs(admin())
        ->get(route('admin.products.index', ['tag' => $korea->id]))
        ->assertOk()
        ->assertSee('Aftermarket bumper')
        ->assertDontSee('Plain bumper');
});

test('product page shows its tags', function () {
    $product = Product::factory()->create();
    $product->tags()->attach(Tag::factory()->create(['name' => 'Original']));

    $this->get(route('products.show', $product))->assertOk()->assertSee('Original');
});
