<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['auth.login_otp.enabled' => true, 'auth.login_otp.fixed_code' => '123456']);
});

function client(): User
{
    return User::factory()->create(['role' => User::ROLE_CLIENT, 'password' => 'secret-password']);
}

test('a customer must enter the code after a correct password', function () {
    $user = client();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password'])
        ->assertRedirect(route('login.otp'));

    $this->assertGuest();
    $this->get(route('login.otp'))->assertOk()->assertSee($user->email);

    $this->post(route('login.otp'), ['code' => '123456'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('a customer goes back to the page they asked for after logging in', function () {
    $user = client();

    $this->get(route('cart.show'))->assertRedirect(route('login'));
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    $this->post(route('login.otp'), ['code' => '123456'])->assertRedirect(route('cart.show'));
});

test('a wrong code does not log in and shows the attempts left', function () {
    $user = client();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    $this->post(route('login.otp'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('too many wrong codes end the login', function () {
    $user = client();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.otp'), ['code' => '000000'])->assertSessionHasErrors('code');
    }

    $this->post(route('login.otp'), ['code' => '000000'])->assertRedirect(route('login'));
    $this->post(route('login.otp'), ['code' => '123456'])->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the code expires', function () {
    $user = client();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    $this->travel(6)->minutes();

    $this->post(route('login.otp'), ['code' => '123456'])->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the code page needs a pending login', function () {
    $this->get(route('login.otp'))->assertRedirect(route('login'));
});

test('a wrong password never reaches the code step', function () {
    $user = client();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->get(route('login.otp'))->assertRedirect(route('login'));
});

test('admins log in without a code', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'password' => 'secret-password']);

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('customers log in without a code when it is turned off', function () {
    config(['auth.login_otp.enabled' => false]);
    $user = client();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('random codes are used when no fixed code is set', function () {
    config(['auth.login_otp.fixed_code' => null]);
    $user = client();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    $this->post(route('login.otp'), ['code' => '123456'])->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('the code can be resent', function () {
    $user = client();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password']);

    $this->from(route('login.otp'))->post(route('login.otp.resend'))
        ->assertRedirect(route('login.otp'))
        ->assertSessionHas('success');

    $this->post(route('login.otp'), ['code' => '123456']);
    $this->assertAuthenticatedAs($user);
});
