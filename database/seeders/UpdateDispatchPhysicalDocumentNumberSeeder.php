<?php

namespace Database\Seeders;

use App\Models\Dispatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UpdateDispatchPhysicalDocumentNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Actualiza los despachos existentes con sus números de documento físico
     * y los nuevos códigos de despacho en formato BOD-XXX-D-YY.
     */
    public function run(): void
    {
        // Mapeo de número de despacho antiguo => [nuevo código, número documento físico]
        $dispatches = [
            // Bodega General Despachos (BOD-001)
            'DIS-20260121-Z1S1WO' => ['BOD-001-D-01', '0031936'],
            'DIS-20260123-DM6AMW' => ['BOD-001-D-02', '0031937'],
            'DIS-20260123-O44BXP' => ['BOD-001-D-03', '0031938'],
            'DIS-20260126-GYGX0J' => ['BOD-001-D-04', '0031939'],
            'DIS-20260126-SFLJL2' => ['BOD-001-D-05', '0031940'],
            'DIS-20260123-EHPQNO' => ['BOD-001-D-06', '0031941'],
            'DIS-20260126-JYNDDJ' => ['BOD-001-D-07', '0031942'],
            'DIS-20260123-SBRLXV' => ['BOD-001-D-08', '0031943'],
            'DIS-20260123-ZQBBUD' => ['BOD-001-D-09', '0031944'],
            'DIS-20260123-CZDICU' => ['BOD-001-D-10', '0031945'],
            'DIS-20260126-EBJXMX' => ['BOD-001-D-11', '0031946'],
            'DIS-20260123-JYTP8S' => ['BOD-001-D-12', '0031947'],
            'DIS-20260123-LGD8X5' => ['BOD-001-D-13', '0031948'],
            'DIS-20260123-WDD9TM' => ['BOD-001-D-14', '0031949'],
            'DIS-20260123-LCE8H8' => ['BOD-001-D-15', '0031950'],
            'DIS-20260123-R2GPIT' => ['BOD-001-D-16', '0031951'],
            'DIS-20260123-9P46UG' => ['BOD-001-D-17', '0031952'],
            'DIS-20260123-PE5ZUS' => ['BOD-001-D-18', '0031953'],
            'DIS-20260123-KTGVFK' => ['BOD-001-D-19', '0031954'],
            'DIS-20260126-JBXCSY' => ['BOD-001-D-20', '0031955'],
            'DIS-20260126-BWOBAG' => ['BOD-001-D-21', '0031956'],

            // Bodega Cocina Despachos (BOD-004)
            'DIS-20260126-5N3TKY' => ['BOD-004-D-01', '0031681'],
            'DIS-20260126-HGNY2A' => ['BOD-004-D-02', '0031682'],
            'DIS-20260126-UZ2SIM' => ['BOD-004-D-03', '0031683'],
            'DIS-20260126-0CZTU1' => ['BOD-004-D-04', '0031684'],
            'DIS-20260126-PH7THJ' => ['BOD-004-D-05', '0031685'],
            'DIS-20260126-KYUMLT' => ['BOD-004-D-06', '0031686'],
            'DIS-20260126-FOI6OJ' => ['BOD-004-D-07', '0031687'],
            'DIS-20260126-7CEOQF' => ['BOD-004-D-08', '0031688'],
            'DIS-20260126-FLLTOL' => ['BOD-004-D-09', '0031689'],
            'DIS-20260126-SKU58Z' => ['BOD-004-D-10', '0031690'],
            'DIS-20260126-BD0QTD' => ['BOD-004-D-11', '0031691'],
            'DIS-20260126-IRYRR1' => ['BOD-004-D-12', '0031692'],
        ];

        $updated = 0;
        $notFound = [];

        foreach ($dispatches as $oldDispatchNumber => $data) {
            [$newDispatchNumber, $physicalDocumentNumber] = $data;

            $dispatch = Dispatch::where('dispatch_number', $oldDispatchNumber)->first();

            if ($dispatch) {
                $dispatch->update([
                    'dispatch_number' => $newDispatchNumber,
                    'slug' => Str::slug($newDispatchNumber),
                    'physical_document_number' => $physicalDocumentNumber,
                ]);
                $updated++;
                $this->command->info("Actualizado: {$oldDispatchNumber} => {$newDispatchNumber} (Doc: {$physicalDocumentNumber})");
            } else {
                $notFound[] = $oldDispatchNumber;
                $this->command->warn("No encontrado: {$oldDispatchNumber}");
            }
        }

        $this->command->newLine();
        $this->command->info("Total actualizados: {$updated}");

        if (count($notFound) > 0) {
            $this->command->warn('Despachos no encontrados: '.count($notFound));
        }
    }
}
