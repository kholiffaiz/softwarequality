<?php

namespace Tests\Feature;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TweetTest extends TestCase
{
    use RefreshDatabase;


    private $user;
    private $tweet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tweet = Tweet::factory()->create([

            'user_id' => $this->user->id,

        ]);
    }

    /** @test */
    public function an_guest_user_cannot_see_tweet_page()
    {
        $response = $this->get('/tweet');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_authenticated_user_can_see_tweet_page()
    {
        // arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/tweet');

        $this->assertAuthenticatedAs($user);
        $response->assertSeeText('Tweet');
    }

    /** @test */
    public function an_authenticated_user_can_post_a_tweet()
    {
        // arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/tweet', [
            'content' => $tweet = 'Test tweet pertama',
        ]);

        $this->assertDatabaseHas('tweets', [
           'user_id' => $user->id,
           'content' => $tweet,
        ]);
    }
    /** @test */
    public function an_authenticated_user_can_not_post_a_tweet_with_empty_content()
    {
        // arrange

        $this->actingAs($this->user);

        $response = $this->post('/tweet', [
            'content' => '',
        ]);

        $response->assertSessionHasErrors([
            'content',
        ]);
    }
    /** @test */
    public function a_tweet_owner_can_edit_their_tweet()
    {

        $this->actingAs($this->user);
        $response = $this->get('/tweet/' . $this->tweet->id . '/edit');

        $response->assertSuccessful();
        $response->assertSeeText($this->tweet->content);
    }

    /** @test */
    public function a_tweet_owner_can_not_edit_other_user_tweet()
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);
        $response = $this->get('/tweet/' . $this->tweet->id . '/edit');

        $response->assertStatus(403);
    }    
    
    /** @test */
    public function a_tweet_owner_can_update_their_tweet()
    {
        $this->actingAs($this->user);

        $this->assertDatabaseHas('tweets', [
           'user_id' => $this->user->id,
           'content' => $this->tweet->content,
        ]);
        
        $response = $this->put('/tweet/' . $this->tweet->id, [
            'content' => $tweet = 'Tweet yang sudah diupdate'
        ]);

        $this->assertDatabaseHas('tweets', [
           'user_id' => $this->user->id,
           'content' => $tweet,
        ]);
    }


    /** @test */
    public function a_tweet_owner_can_delete_their_tweet()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->delete('/tweet/' . $this->tweet->id);

        $this->assertDeleted('tweets', [
            'id' => $this->tweet->id,
        ]);
    }

   /** @test */
   public function a_user_can_not_update_other_user_tweet()
   {
       $otherUser = User::factory()->create();

       $this->actingAs($otherUser);
       $response = $this->put('/tweet/' . $this->tweet->id, [
           'content' => 'coba update tweet orang lain'
       ]);

       $response->assertStatus(403);
   }

    /** @test */
    public function an_authenticated_user_can_see_detail_tweet_page()
    {
        $this->actingAs($this->user);

        $response = $this->get('/tweet/' . $this->tweet->id);

        $response->assertSuccessful();
        $response->assertSeeText($this->tweet->content);
    }


}
