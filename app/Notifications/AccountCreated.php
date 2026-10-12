<?php

namespace App\Notifications;

use Carbon\CarbonInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome email for an account an admin created. It carries a password reset
 * token so the new user chooses their own password; the admin never sees it.
 */
class AccountCreated extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresIn = CarbonInterval::minutes(config('auth.passwords.welcome.expire'))->cascade()->forHumans();

        return (new MailMessage)
            ->subject('Your '.config('app.name').' account is ready')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your IT administrator has created a '.config('app.name').' account for you. Use it to report IT issues and follow their progress.')
            ->action('Set your password', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email, 'welcome' => 1]))
            ->line("This link expires in {$expiresIn}. After that, use \"Forgot password?\" on the sign-in page.");
    }
}
