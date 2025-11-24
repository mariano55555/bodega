<?php

namespace App\Notifications;

use App\Models\InventoryClosure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClosureCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public InventoryClosure $closure,
        public string $warehouseName,
        public int $totalProducts,
        public float $totalValue
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $monthName = $monthNames[$this->closure->month] ?? $this->closure->month;

        return (new MailMessage)
            ->subject("Cierre Mensual Completado - {$monthName} {$this->closure->year}")
            ->greeting('Cierre Mensual Completado')
            ->line("El cierre de inventario para **{$monthName} {$this->closure->year}** ha sido completado.")
            ->line("**Bodega:** {$this->warehouseName}")
            ->line("**Total productos:** {$this->totalProducts}")
            ->line("**Valor total:** $".number_format($this->totalValue, 2))
            ->action('Ver Cierres', route('closures.index'))
            ->line('El inventario ha sido cerrado correctamente.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $monthName = $monthNames[$this->closure->month] ?? $this->closure->month;

        return [
            'type' => 'closure_completed',
            'icon' => 'lock-closed',
            'color' => 'green',
            'closure_id' => $this->closure->id,
            'closure_number' => $this->closure->closure_number,
            'month' => $this->closure->month,
            'year' => $this->closure->year,
            'warehouse_name' => $this->warehouseName,
            'total_products' => $this->totalProducts,
            'total_value' => $this->totalValue,
            'message' => "Cierre completado: {$monthName} {$this->closure->year} - {$this->warehouseName}",
            'url' => route('closures.index'),
        ];
    }
}
