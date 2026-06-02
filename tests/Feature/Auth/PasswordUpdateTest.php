<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'W0ss_L4r4v3l_Proj3ct!@#_2026_S3cure',
            'password_confirmation' => 'W0ss_L4r4v3l_Proj3ct!@#_2026_S3cure',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('W0ss_L4r4v3l_Proj3ct!@#_2026_S3cure', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'W0ss_L4r4v3l_Proj3ct!@#_2026_S3cure',
            'password_confirmation' => 'W0ss_L4r4v3l_Proj3ct!@#_2026_S3cure',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
