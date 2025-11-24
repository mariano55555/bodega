<?php

namespace App\Mail;

use App\Models\InventoryAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public InventoryAlert $alert,
        public array $additionalAlerts = []
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->alert->priority) {
            'critical' => '🚨 ALERTA CRÍTICA de Inventario',
            'high' => '⚠️ Alerta de Alta Prioridad de Inventario',
            'medium' => '📢 Alerta de Inventario',
            default => 'ℹ️ Notificación de Inventario',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alerts.notification',
            with: [
                'alert' => $this->alert,
                'additionalAlerts' => $this->additionalAlerts,
                'priorityColor' => $this->getPriorityColor(),
                'priorityLabel' => $this->getPriorityLabel(),
                'typeLabel' => $this->getTypeLabel(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the color for the priority
     */
    protected function getPriorityColor(): string
    {
        return match ($this->alert->priority) {
            'critical' => '#DC2626',
            'high' => '#EA580C',
            'medium' => '#F59E0B',
            default => '#3B82F6',
        };
    }

    /**
     * Get the label for the priority
     */
    protected function getPriorityLabel(): string
    {
        return match ($this->alert->priority) {
            'critical' => 'Crítica',
            'high' => 'Alta',
            'medium' => 'Media',
            default => 'Baja',
        };
    }

    /**
     * Get the label for the alert type
     */
    protected function getTypeLabel(): string
    {
        return match ($this->alert->alert_type) {
            'low_stock' => 'Stock Bajo',
            'out_of_stock' => 'Sin Stock',
            'expiring_soon' => 'Próximo a Vencer',
            'expired' => 'Vencido',
            default => 'Alerta de Inventario',
        };
    }
}
