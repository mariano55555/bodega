{{--
    Shared body of the document correction screens.
    Expects: $lines (array), $products (collection), $withPrice (bool), $cancelUrl (string)
--}}
<flux:card>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:heading size="lg">Productos</flux:heading>
        <flux:button size="sm" variant="outline" icon="plus" wire:click="addLine">Agregar producto</flux:button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="min-w-72">Producto</flux:table.column>
                <flux:table.column class="w-40">Cantidad</flux:table.column>
                @if ($withPrice)
                    <flux:table.column class="w-40">Costo Unit.</flux:table.column>
                @endif
                <flux:table.column class="min-w-48">Notas</flux:table.column>
                <flux:table.column class="w-12"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($lines as $index => $line)
                    <flux:table.row wire:key="correction-line-{{ $index }}">
                        <flux:table.cell>
                            <flux:select variant="listbox" searchable wire:model="lines.{{ $index }}.product_id" placeholder="Buscar producto...">
                                @foreach ($products as $product)
                                    <flux:select.option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' - '.$product->sku : '' }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="lines.{{ $index }}.product_id" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:input type="number" step="0.00001" min="0" wire:model="lines.{{ $index }}.quantity" class="text-right" />
                            <flux:error name="lines.{{ $index }}.quantity" />
                        </flux:table.cell>
                        @if ($withPrice)
                            <flux:table.cell>
                                <flux:input type="number" step="0.00001" min="0" wire:model="lines.{{ $index }}.unit_price" class="text-right" />
                                <flux:error name="lines.{{ $index }}.unit_price" />
                            </flux:table.cell>
                        @endif
                        <flux:table.cell>
                            <flux:input wire:model="lines.{{ $index }}.notes" placeholder="Opcional" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})" aria-label="Quitar producto" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:error name="lines" />
</flux:card>

<flux:card class="space-y-4">
    <flux:field>
        <flux:label badge="Requerido">Motivo de la corrección</flux:label>
        <flux:textarea wire:model="reason" rows="3" placeholder="Ej: el traslado era para Zootecnia y se registró a Agroindustria" />
        <flux:error name="reason" />
    </flux:field>

    @error('correction')
        <flux:callout icon="exclamation-triangle" color="red">
            <flux:callout.heading>No se aplicó la corrección</flux:callout.heading>
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <div class="flex flex-wrap justify-end gap-2">
        <flux:button variant="ghost" href="{{ $cancelUrl }}" wire:navigate>Cancelar</flux:button>
        <flux:button variant="primary" icon="check" wire:click="save"
            wire:confirm="¿Aplicar la corrección? El inventario se ajustará solo por la diferencia y quedará registrado en el historial del documento."
            wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Aplicar corrección</span>
            <span wire:loading wire:target="save">Aplicando...</span>
        </flux:button>
    </div>
</flux:card>
