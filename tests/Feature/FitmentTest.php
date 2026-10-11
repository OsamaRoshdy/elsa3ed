<?php

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Fitment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a model under a brand', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);

    $this->actingAs(admin())
        ->post(route('admin.car-models.store'), ['brand_id' => $kia->id, 'name' => 'Cerato'])
        ->assertRedirect(route('admin.car-models.index', ['brand' => $kia->id]));

    expect($kia->carModels()->pluck('name')->all())->toBe(['Cerato']);
});

test('model names are unique per brand only', function () {
    $kia = Brand::factory()->create();
    $hyundai = Brand::factory()->create();
    CarModel::factory()->for($kia)->create(['name' => 'Rio']);

    $this->actingAs(admin())
        ->post(route('admin.car-models.store'), ['brand_id' => $kia->id, 'name' => 'Rio'])
        ->assertSessionHasErrors('name');

    $this->actingAs(admin())
        ->post(route('admin.car-models.store'), ['brand_id' => $hyundai->id, 'name' => 'Rio'])
        ->assertSessionHasNoErrors();
});

test('admin can rename and delete a model', function () {
    $carModel = CarModel::factory()->create(['name' => 'Cerrato']);
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.car-models.edit', $carModel))->assertOk();
    $this->actingAs($admin)->put(route('admin.car-models.update', $carModel), ['brand_id' => $carModel->brand_id, 'name' => 'Cerato']);

    expect($carModel->fresh()->name)->toBe('Cerato');

    $this->actingAs($admin)->delete(route('admin.car-models.destroy', $carModel));

    expect(CarModel::count())->toBe(0);
});

test('clients cannot manage models', function () {
    $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

    $this->actingAs($client)->get(route('admin.car-models.index'))->assertForbidden();
});

test('admin can link a product to models with year ranges', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $cerato = CarModel::factory()->for($kia)->create(['name' => 'Cerato']);
    $sportage = CarModel::factory()->for($kia)->create(['name' => 'Sportage']);
    $product = Product::factory()->create();
    $admin = admin();

    $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Kia Cerato');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'fitments' => [
                ['car_model_id' => $cerato->id, 'year_from' => 2015, 'year_to' => 2017],
                ['car_model_id' => $sportage->id, 'year_from' => 2020, 'year_to' => null],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.products.index'));

    $fitments = $product->fitments()->orderBy('year_from')->get();

    expect($fitments)->toHaveCount(2)
        ->and($fitments[0]->only(['car_model_id', 'year_from', 'year_to']))->toBe(['car_model_id' => $cerato->id, 'year_from' => 2015, 'year_to' => 2017])
        ->and($fitments[1]->year_to)->toBeNull()
        ->and($product->brands()->pluck('name')->all())->toBe(['Kia']);

    // Saving again replaces the fitments.
    $this->actingAs($admin)->put(route('admin.products.update', $product), ['fitments' => [['car_model_id' => $cerato->id, 'year_from' => 2018, 'year_to' => 2018]]]);

    expect($product->fitments()->count())->toBe(1);

    $this->actingAs($admin)->put(route('admin.products.update', $product), []);

    expect($product->fitments()->count())->toBe(0);
});

test('fitment "to" year cannot be before the "from" year', function () {
    $carModel = CarModel::factory()->create();

    $this->actingAs(admin())
        ->put(route('admin.products.update', Product::factory()->create()), [
            'fitments' => [['car_model_id' => $carModel->id, 'year_from' => 2017, 'year_to' => 2015]],
        ])
        ->assertSessionHasErrors('fitments.0.year_to');
});

test('fitment years must be realistic and the model must exist', function () {
    $this->actingAs(admin())
        ->put(route('admin.products.update', Product::factory()->create()), [
            'fitments' => [['car_model_id' => 999, 'year_from' => 1800, 'year_to' => Fitment::lastYear() + 5]],
        ])
        ->assertSessionHasErrors(['fitments.0.car_model_id', 'fitments.0.year_from', 'fitments.0.year_to']);
});

test('catalogue finds the parts that fit a model and year', function () {
    $kia = Brand::factory()->create(['name' => 'Kia']);
    $cerato = CarModel::factory()->for($kia)->create(['name' => 'Cerato']);
    $rio = CarModel::factory()->for($kia)->create(['name' => 'Rio']);

    $mirror = Product::factory()->create(['name' => 'Cerato mirror 2015-2017']);
    $mirror->fitments()->create(['car_model_id' => $cerato->id, 'year_from' => 2015, 'year_to' => 2017]);
    $filter = Product::factory()->create(['name' => 'Cerato filter all years']);
    $filter->fitments()->create(['car_model_id' => $cerato->id]);
    $newBumper = Product::factory()->create(['name' => 'Cerato bumper 2019 onwards']);
    $newBumper->fitments()->create(['car_model_id' => $cerato->id, 'year_from' => 2019]);
    $rioLamp = Product::factory()->create(['name' => 'Rio lamp']);
    $rioLamp->fitments()->create(['car_model_id' => $rio->id]);
    collect([$mirror, $filter, $newBumper, $rioLamp])->each(fn ($product) => $product->brands()->attach($kia));

    $this->get(route('products.index', ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2016]))
        ->assertOk()
        ->assertSee('Cerato mirror 2015-2017')
        ->assertSee('Cerato filter all years')
        ->assertDontSee('Cerato bumper 2019 onwards')
        ->assertDontSee('Rio lamp');

    $this->get(route('products.index', ['brand' => $kia->id, 'model' => $cerato->id, 'year' => 2021]))
        ->assertDontSee('Cerato mirror 2015-2017')
        ->assertSee('Cerato filter all years')
        ->assertSee('Cerato bumper 2019 onwards');

    $this->get(route('products.index', ['brand' => $kia->id, 'model' => $cerato->id]))
        ->assertSee('Cerato mirror 2015-2017')
        ->assertSee('Cerato bumper 2019 onwards')
        ->assertDontSee('Rio lamp');
});

test('product page lists the vehicles it fits', function () {
    $cerato = CarModel::factory()->for(Brand::factory()->create(['name' => 'Kia']))->create(['name' => 'Cerato']);
    $product = Product::factory()->create();
    $product->fitments()->create(['car_model_id' => $cerato->id, 'year_from' => 2015, 'year_to' => 2017]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSeeInOrder(['Kia', 'Cerato', '2015 – 2017']);
});

test('fitment years are labelled clearly', function (?int $from, ?int $to, string $label) {
    expect((new Fitment(['year_from' => $from, 'year_to' => $to]))->yearsLabel())->toBe($label);
})->with([
    [2015, 2017, '2015 – 2017'],
    [2016, 2016, '2016'],
    [2019, null, '2019+'],
    [null, 2012, '– 2012'],
]);
