<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route(
            'password.reset',
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ],
            false
        ));

        return (new MailMessage)
            ->subject('Recuperación de contraseña - DocGen')
            ->greeting('Estimado(a) usuario:')
            ->line('Se recibió una solicitud para restablecer la contraseña de su cuenta.')
            ->line('Si usted realizó esta solicitud, haga clic en el siguiente botón.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si usted no solicitó el cambio de contraseña, puede ignorar este mensaje.')
            ->salutation('Sistema de Adquisiciones y Servicios - DocGen');
    }
}