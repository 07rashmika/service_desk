<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared behaviour for ticket notifications: always stored for the bell and pushed
 * live over Reverb, and emailed when the person hasn't switched that email off.
 */
abstract class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * The email setting that controls this notification, or null for in-app only.
     */
    abstract protected function preference(): ?NotificationPreference;

    /**
     * One short sentence, shown in the bell, the notifications page and the email.
     */
    abstract public function message(User $notifiable): string;

    abstract protected function icon(): string;

    abstract protected function color(): string;

    /**
     * Extra lines for the email, after the message and before the button.
     */
    protected function withMailDetails(MailMessage $mail, User $notifiable): MailMessage
    {
        return $mail;
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        if (! $notifiable->is_active) {
            return [];
        }

        $channels = ['database', 'broadcast'];

        if ($this->preference() && $notifiable->wantsEmail($this->preference())) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array{ticket_id: int, reference: string, title: string, message: string, icon: string, color: string, url: string}
     */
    public function toArray(User $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'title' => $this->ticket->title,
            'message' => $this->message($notifiable),
            'icon' => $this->icon(),
            'color' => $this->color(),
            'url' => $this->ticket->urlFor($notifiable),
        ];
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            ...$this->toArray($notifiable),
            'id' => $this->id,
            'open_url' => route('notifications.open', $this->id),
        ]);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[{$this->ticket->reference}] {$this->message($notifiable)}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message($notifiable));

        return $this->withMailDetails($mail, $notifiable)
            ->action('Open the ticket', $this->ticket->urlFor($notifiable))
            ->line('You can choose which emails you get on your profile page.');
    }
}
