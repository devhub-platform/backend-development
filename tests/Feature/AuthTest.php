<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
//    public function it_can_register_a_new_user()
//    {
//        $userData = [
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//            'password' => 'Password123!',
//            'password_confirmation' => 'Password123!',
//        ];
//
//        $response = $this->postJson('/api/v1/register', $userData);
//
//        $response->assertStatus(201)
//            ->assertJsonStructure([
//                'message',
//                'data',
//                'token',
//            ]);
//
//        $this->assertDatabaseHas('users', [
//            'email' => 'test@example.com',
//        ]);
//    }
//
//    /** @test */
//    public function it_validates_registration_data()
//    {
//        $response = $this->postJson('/api/v1/register', []);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['name', 'email', 'password']);
//    }
//
//    /** @test */
//    public function it_requires_unique_email_for_registration()
//    {
//        User::factory()->create(['email' => 'test@example.com']);
//
//        $userData = [
//            'name' => 'Test User',
//            'username' => 'testuser2',
//            'email' => 'test@example.com',
//            'password' => 'Password123!',
//            'password_confirmation' => 'Password123!',
//        ];
//
//        $response = $this->postJson('/api/v1/register', $userData);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['email']);
//    }
//
//    /** @test */
//    public function it_requires_unique_username_for_registration()
//    {
//        User::factory()->create(['username' => 'testuser']);
//
//        $userData = [
//            'name' => 'Test User',
//            'username' => 'testuser',
//            'email' => 'test2@example.com',
//            'password' => 'Password123!',
//            'password_confirmation' => 'Password123!',
//        ];
//
//        $response = $this->postJson('/api/v1/register', $userData);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['username']);
//    }
//
//    /** @test */
//    public function it_can_login_with_valid_credentials()
//    {
//        $user = User::factory()->create([
//            'email' => 'test@example.com',
//            'password' => Hash::make('Password123!'),
//        ]);
//
//        $credentials = [
//            'email' => 'test@example.com',
//            'password' => 'Password123!',
//        ];
//
//        $response = $this->postJson('/api/v1/login', $credentials);
//
//        $response->assertStatus(200)
//            ->assertJsonStructure([
//                'token',
//            ]);
//    }
//
//    /** @test */
////    public function it_cannot_login_with_invalid_credentials()
////    {
////        $user = User::factory()->create([
////            'email' => 'test@example.com',
////            'password' => Hash::make('Password123!'),
////        ]);
////
////        $credentials = [
////            'email' => 'test@example.com',
////            'password' => 'WrongPassword123!',
////        ];
////
////        $response = $this->postJson('/api/v1/login', $credentials);
////
////        $response->assertStatus(401);
////    }
////
////    /** @test */
////    public function it_can_get_authenticated_user()
////    {
////        $user = User::factory()->create();
////
////        $response = $this->actingAs($user, 'api')
////            ->getJson('/api/v1/me');
////
////        $response->assertStatus(200)
////            ->assertJson([
////                'email' => $user->email,
////            ]);
////    }
//
//    /** @test */
////    public function it_can_logout()
////    {
////        $user = User::factory()->create(['name' => 'Test User']);
////
////        $response = $this->actingAs($user, 'api')
////            ->postJson('/api/v1/logout');
////
////        $response->assertStatus(200)
////            ->assertJson([
////                'message' => "User Test User successfully logged out.",
////            ]);
////    }
////
////    /** @test */
////    public function it_can_refresh_token()
////    {
////        $user = User::factory()->create([
////            'email' => 'test@example.com',
////            'password' => Hash::make('Password123!'),
////        ]);
////
////        // First login to get a real JWT token
////        $loginResponse = $this->postJson('/api/v1/login', [
////            'email' => 'test@example.com',
////            'password' => 'Password123!',
////        ]);
////
////        $token = $loginResponse->json('access_token');
////
////        // Use the token to refresh
////        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
////            ->postJson('/api/v1/refresh');
////
////        $response->assertStatus(200)
////            ->assertJsonStructure([
////                'message',
////                'token',
////                'data',
////            ]);
////    }
//
//    /** @test */
//    public function it_requires_password_confirmation()
//    {
//        $userData = [
//            'name' => 'Test User',
//            'username' => 'testuser',
//            'email' => 'test@example.com',
//            'password' => 'Password123!',
//            'password_confirmation' => 'DifferentPass123!',
//        ];
//
//        $response = $this->postJson('/api/v1/register', $userData);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['password']);
//    }
//
//    /** @test */
//    public function it_can_send_password_reset_otp()
//    {
//        $user = User::factory()->create(['email' => 'test@example.com']);
//
//        $response = $this->postJson('/api/v1/password/forgot', [
//            'email' => 'test@example.com',
//        ]);
//
//        $response->assertStatus(200)
//            ->assertJson([
//                'message' => 'OTP sent to your email.',
//            ]);
//    }
//
//    /** @test */
////    public function it_cannot_send_password_reset_otp_for_nonexistent_user()
////    {
////        $response = $this->postJson('/api/v1/password/forgot', [
////            'email' => 'nonexistent@example.com',
////        ]);
////
////        $response->assertStatus(404);
////    }
////
////    /** @test */
////    public function it_can_send_email_verification_otp()
////    {
////        $user = User::factory()->unverified()->create();
////
////        $response = $this->actingAs($user, 'api')
////            ->postJson('/api/v1/email/send-otp');
////
////        $response->assertStatus(200);
////    }
//
////    /** @test */
////    public function it_can_verify_email_with_otp()
////    {
////        $user = User::factory()->unverified()->create([
////            'otp' => '123456',
////            'otp_expires_at' => now()->addMinutes(10),
////        ]);
////
////        $response = $this->actingAs($user, 'api')
////            ->postJson('/api/v1/email/verify-otp', [
////                'otp' => '123456',
////            ]);
////
////        $response->assertStatus(200);
////
////        $user->refresh();
////        $this->assertNotNull($user->email_verified_at);
////    }
//
//    /** @test */
////    public function it_cannot_verify_email_with_invalid_otp()
////    {
////        $user = User::factory()->unverified()->create([
////            'otp' => '123456',
////            'otp_expires_at' => now()->addMinutes(10),
////        ]);
////
////        $response = $this->actingAs($user, 'api')
////            ->postJson('/api/v1/email/verify-otp', [
////                'otp' => '999999',
////            ]);
////
////        $response->assertStatus(400);
////    }
////
////    /** @test */
////    public function it_cannot_verify_email_with_expired_otp()
////    {
////        $user = User::factory()->unverified()->create([
////            'otp' => '123456',
////            'otp_expires_at' => now()->subMinutes(10),
////        ]);
////
////        $response = $this->actingAs($user, 'api')
////            ->postJson('/api/v1/email/verify-otp', [
////                'otp' => '123456',
////            ]);
////
////        $response->assertStatus(400);
////    }
//
//    /** @test */
//    public function unauthenticated_user_cannot_access_protected_routes()
//    {
//        $response = $this->getJson('/api/v1/me');
//
//        $response->assertStatus(401);
//    }
}

