<?php

namespace App\Imports;

use App\Models\InventoryAdjustment;
use Maatwebsite\Excel\Concerns\ToModel;

class InventoryAdjustmentsImport implements ToModel
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new InventoryAdjustment([
            //
        ]);
    }
}
