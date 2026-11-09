{{--
    History of corrections applied to a document. Expects: $corrections (collection of DocumentCorrection)
--}}
@if ($corrections->isNotEmpty())
    <flux:card>
        <flux:heading size="lg">Correcciones aplicadas</flux:heading>
        <flux:text class="mt-1">Cambios hechos después de que el documento movió inventario.</flux:text>

        <div class="mt-4 space-y-3">
            @foreach ($corrections as $correction)
                <div wire:key="correction-{{ $correction->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200">{{ $correction->creator?->name ?? 'Usuario eliminado' }}</flux:text>
                        <flux:text class="text-xs">{{ $correction->created_at?->format('d/m/Y H:i') }}</flux:text>
                    </div>
                    <flux:text class="mt-1">{{ $correction->reason }}</flux:text>
                    @php
                        $describe = fn (?array $snapshot) => collect($snapshot['lines'] ?? [])
                            ->map(fn ($line) => ($line['product'] ?? '?').' x '.rtrim(rtrim(number_format((float) ($line['quantity'] ?? 0), 5, '.', ''), '0'), '.'))
                            ->join(', ');
                        $place = fn (?array $snapshot) => isset($snapshot['to_warehouse'])
                            ? ($snapshot['from_warehouse'] ?? '?').' → '.$snapshot['to_warehouse']
                            : ($snapshot['warehouse'] ?? '');
                    @endphp
                    <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-zinc-500 dark:text-zinc-400 md:grid-cols-2">
                        <div><span class="font-medium">Antes:</span> {{ $place($correction->before) }} · {{ $describe($correction->before) }}</div>
                        <div><span class="font-medium">Después:</span> {{ $place($correction->after) }} · {{ $describe($correction->after) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>
@endif
