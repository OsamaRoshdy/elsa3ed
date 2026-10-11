<?php

use App\Models\Product;
use App\Models\User;
use App\Support\SkuMask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('skus keep only their first and last characters', function (string $sku, string $masked) {
    expect(SkuMask::mask($sku))->toBe($masked);
})->with([
    ['58101-2HA10', '58••••••A10'],
    ['283212B850', '28•••••850'],
    ['A7010', 'A•••0'],
    ['1102', '1•••'],
    ['7', '•'],
]);

test('guests and customers see the masked sku in the catalogue', function () {
    $product = Product::factory()->create(['sku' => '58101-2HA10']);
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->get(route('products.index'))->assertSee('58••••••A10')->assertDontSee('58101-2HA10');
    $this->get(route('products.show', $product))->assertSee('58••••••A10')->assertDontSee('58101-2HA10');
    $this->actingAs($client)->get(route('products.show', $product))->assertSee('58••••••A10')->assertDontSee('58101-2HA10');
});

test('admins see the full sku', function () {
    $product = Product::factory()->create(['sku' => '58101-2HA10']);

    $this->actingAs(admin())->get(route('products.show', $product))->assertSee('58101-2HA10');
});

test('searching by the full sku still finds the product', function () {
    Product::factory()->create(['name' => 'Brake pads F', 'sku' => '58101-2HA10']);

    $this->get(route('products.index', ['q' => '58101-2HA10']))->assertSee('Brake pads F');
});

test('the sku is also masked inside the product description', function () {
    $product = Product::factory()->create(['sku' => 'CBKH-39(CTR)', 'description' => 'CBKH-39(CTR)']);

    $this->get(route('products.show', $product))->assertDontSee('CBKH-39(CTR)');
    $this->actingAs(admin())->get(route('products.show', $product))->assertSee('CBKH-39(CTR)');
});
