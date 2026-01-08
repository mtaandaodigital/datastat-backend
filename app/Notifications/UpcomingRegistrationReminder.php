<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingRegistrationReminder extends Notification
{
    use Queueable;

    protected $registrant;
    protected $customMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct($registrant, $customMessage = null)
    {
        $this->registrant = $registrant;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $registrant = $this->registrant;
        $schedule = $registrant->schedule;
        $course = $schedule ? $schedule->course : null;

        $message = (new MailMessage)
            ->subject('Reminder: Upcoming Course Registration')
            ->greeting('Dear ' . $registrant->full_name . ',')
            ->line('This is a reminder for your upcoming course registration.')
            ->line('Course: ' . ($course ? $course->title : $registrant->title_course))
            ->line('Location: ' . ($schedule ? $schedule->location : 'N/A'))
            ->line('Start Date: ' . ($schedule ? \Carbon\Carbon::parse($schedule->start)->format('M d, Y') : 'N/A'))
            ->line('End Date: ' . ($schedule ? \Carbon\Carbon::parse($schedule->end)->format('M d, Y') : 'N/A'));

        if ($this->customMessage) {
            $message->line($this->customMessage);
        }

        $message->action('View Details', url('/admin/registrants/' . $registrant->registrants_id))
            ->line('If you have any questions, please contact us.')
            ->salutation('Best regards, DataStat Team');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
