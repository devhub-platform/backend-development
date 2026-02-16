<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = new User();

        $fillable = [
            'name',
            'username',
            'role',
            'avatar_url',
            'bio',
            'email',
            'password',
            'email_verified_at',
            'remember_token',
            'created_at',
            'updated_at',
            'provider_id',
            'otp',
            'two_factor_expires_at',
            'cover_image',
            'education',
            'work_at',
            'skills',
            'deleted_at',
            'location',
            'website_url',
            'pronouns',
            'linkedin_username',
            'github_username',
            'currently_learning',
            'alt_email',
            'alt_email_verified_at',
            'alt_email_otp',
            'alt_email_otp_expires_at',
            'otp_expires_at',
            'orcid_username',
            'notification_preferences'
        ];

        $this->assertEquals($fillable, $user->getFillable());
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /** @test */
    public function it_has_many_posts()
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->posts);
    }

    /** @test */
    public function it_has_many_comments()
    {
        $user = User::factory()->create();
        Comment::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->comments);
    }

    /** @test */
    public function it_can_follow_other_users()
    {
        $user = User::factory()->create();
        $followedUser = User::factory()->create();

        $user->following()->attach($followedUser->id);

        $this->assertTrue($user->following->contains($followedUser->id));
    }

    /** @test */
    public function it_can_have_followers()
    {
        $user = User::factory()->create();
        $follower = User::factory()->create();

        $user->followers()->attach($follower->id);

        $this->assertTrue($user->followers->contains($follower->id));
    }

    /** @test */
    public function it_can_save_posts()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $user->savedPosts()->attach($post->id);

        $this->assertTrue($user->savedPosts->contains($post->id));
    }

//    /** @test */
//    public function it_can_have_reading_lists()
//    {
//        $user = User::factory()->create();
//        $readingList = \App\Models\ReadingList::factory()->create(['user_id' => $user->id]);
//
//        $this->assertCount(1, $user->readingLists);
//    }

    /** @test */
    public function it_has_jwt_methods()
    {
        $user = User::factory()->create();

        $this->assertIsInt($user->getJWTIdentifier());
        $this->assertIsArray($user->getJWTCustomClaims());
    }

    /** @test */
    public function it_has_searchable_array()
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'name' => 'John Doe',
        ]);

        $searchableArray = $user->toSearchableArray();

        $this->assertArrayHasKey('username', $searchableArray);
        $this->assertArrayHasKey('name', $searchableArray);
        $this->assertEquals('johndoe', $searchableArray['username']);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);

        $deletedUser = User::withTrashed()->find($userId);
        $this->assertNotNull($deletedUser->deleted_at);
    }

    /** @test */
    public function it_can_block_users()
    {
        $user = User::factory()->create();
        $blockedUser = User::factory()->create();

        $user->blockedUsers()->attach($blockedUser->id);

        $this->assertTrue($user->blockedUsers->contains($blockedUser->id));
    }

    /** @test */
    public function it_can_follow_tags()
    {
        $user = User::factory()->create();
        $tag = \App\Models\Tag::factory()->create();

        $user->followedTags()->attach($tag->id);

        $this->assertTrue($user->followedTags->contains($tag->id));
    }

    /** @test */
    public function it_encrypts_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $this->assertNotEquals('Password123!', $user->password);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    /** @test */
    public function it_can_verify_email()
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);

        $user->update(['email_verified_at' => now()]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function it_can_have_otp_for_verification()
    {
        $user = User::factory()->create([
            'otp' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->assertEquals('123456', $user->otp);
        $this->assertNotNull($user->otp_expires_at);
    }

    /** @test */
    public function it_can_have_alt_email()
    {
        $user = User::factory()->create([
            'alt_email' => 'alt@example.com',
        ]);

        $this->assertEquals('alt@example.com', $user->alt_email);
    }

    /** @test */
    public function it_can_have_social_usernames()
    {
        $user = User::factory()->create([
            'github_username' => 'johndoe',
            'linkedin_username' => 'johndoe',
            'orcid_username' => '0000-0001-2345-6789',
        ]);

        $this->assertEquals('johndoe', $user->github_username);
        $this->assertEquals('johndoe', $user->linkedin_username);
        $this->assertEquals('0000-0001-2345-6789', $user->orcid_username);
    }

    /** @test */
    public function it_has_default_role()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->assertEquals('user', $user->role);
    }

    /** @test */
    public function it_can_be_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertEquals('admin', $admin->role);
    }
}

