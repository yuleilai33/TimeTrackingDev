<?php

namespace newlifecfo\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Action;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use newlifecfo\User;

class ConfirmReports extends Notification //implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $user;

    public function __construct(User $user)
    {
        //
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject('Please Confirm Your Team\'s Reports in Last Month')
            ->greeting('Hello, ' . $this->user->first_name . '!')
            ->line('Your team\'s last month reports are available to be confirmed, please login to confirm.')
            ->action('Confirm Reports', url('/approval/hour?summary=1'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
