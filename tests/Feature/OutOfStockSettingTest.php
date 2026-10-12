<?php

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('out-of-stock products are shown in the store by default', function () {
    $soldOut = Product::factory()->create(['name' => 'Sold out bumper', 'stock_quantity' => 0]);

    $this->get(route('products.index'))->assertOk()->assertSee('Sold out bumper');
    $this->get(route('products.show', $soldOut))->assertOk();
});

test('out-of-stock products are hidden from the store when the setting is off', function () {
    Setting::set('catalogue_show_out_of_stock', '0');

    $soldOut = Product::factory()->create(['name' => 'Sold out bumper', 'stock_quantity' => 0]);
    Product::factory()->create(['name' => 'Stocked headlight', 'stock_quantity' => 5]);
    Product::factory()->create(['name' => 'Untracked wiper', 'stock_quantity' => null]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertDontSee('Sold out bumper')
        ->assertSee('Stocked headlight')
        ->assertSee('Untracked wiper')
        ->assertDontSee(__('Available only'));

    $this->get(route('products.show', $soldOut))->assertNotFound();
});

test('admins can turn off showing out-of-stock products', function () {
    $this->actingAs(admin())
        ->put(route('admin.settings.update'), [
            'catalogue_sort' => Product::DEFAULT_SORT,
            'catalogue_client_sort' => '1',
            'catalogue_show_out_of_stock' => '0',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Product::storeShowsOutOfStock())->toBeFalse();

    $this->actingAs(admin())->get(route('admin.settings.edit'))->assertOk()->assertSee('catalogue_show_out_of_stock', false);
});
