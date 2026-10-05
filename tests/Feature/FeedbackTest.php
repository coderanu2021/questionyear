<?php

namespace Tests\Feature;

use App\Mail\MessageSubmitted;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_page_and_footer_link_are_public(): void
    {
        $this->get(route('feedback'))->assertOk()->assertSee('Share your feedback')->assertSee('name="rating"', false);
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('feedback').'"', false);
    }

    public function test_guest_feedback_is_saved_and_visible_only_to_admins(): void
    {
        Mail::fake();
        $data = ['name' => 'Learner', 'email' => 'learner@example.com', 'rating' => 4, 'message' => 'Please add more daily quiz questions.', 'type' => 'contact'];
        $this->post(route('feedback.send'), $data)->assertRedirect(route('feedback'))->assertSessionHas('status');
        $this->assertDatabaseHas('messages', ['email' => 'learner@example.com', 'type' => 'feedback', 'rating' => 4]);
        Mail::assertSent(MessageSubmitted::class, fn (MessageSubmitted $mail): bool => $mail->hasTo('questionyear2026@gmail.com') && $mail->hasReplyTo('learner@example.com') && $mail->submission->rating === 4);
        $this->get(route('feedback'))->assertDontSee($data['message']);
        $this->get('/admin/messages')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/admin/messages')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/messages')->assertOk()->assertViewHas('state', fn (array $state): bool => $state['messages'][0]['type'] === 'feedback' && $state['messages'][0]['rating'] === 4);
    }

    public function test_invalid_feedback_is_rejected_and_logged_in_details_are_prefilled(): void
    {
        Mail::fake();
        $this->post(route('feedback.send'), ['name' => '', 'email' => 'invalid', 'rating' => 6, 'message' => 'short'])->assertSessionHasErrors(['name', 'email', 'rating', 'message']);
        $this->assertDatabaseCount('messages', 0);
        Mail::assertNothingSent();
        $this->app['session']->forget('_old_input');
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('feedback'))->assertSee('value="'.e($user->name).'"', false)->assertSee('value="'.e($user->email).'"', false);
        $this->post(route('contact.send'), ['name' => 'Contact user', 'email' => 'contact@example.com', 'message' => 'A regular contact message.'])->assertRedirect();
        $this->assertDatabaseHas('messages', ['email' => 'contact@example.com', 'type' => 'contact', 'rating' => null]);
        Mail::assertSent(MessageSubmitted::class, fn (MessageSubmitted $mail): bool => $mail->hasTo('questionyear2026@gmail.com') && $mail->hasReplyTo('contact@example.com'));
        $mail = new MessageSubmitted(Message::firstOrFail());
        $this->assertStringContainsString('A regular contact message.', $mail->render());
    }

    public function test_mail_failure_does_not_discard_a_submitted_message(): void
    {
        Mail::shouldReceive('to')->once()->with('questionyear2026@gmail.com')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('feedback.send'), ['name' => 'Learner', 'email' => 'learner@example.com', 'rating' => 5, 'message' => 'Please add more daily quiz questions.'])
            ->assertRedirect(route('feedback'))->assertSessionHas('status');
        $this->assertDatabaseHas('messages', ['email' => 'learner@example.com', 'type' => 'feedback']);
    }
}
