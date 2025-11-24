<?php

namespace App\Http\Controllers;

use App\Exports\InventoryClosureExport;
use App\Models\InventoryClosure;
use Maatwebsite\Excel\Facades\Excel;

class InventoryClosureExportController extends Controller
{
    public function __invoke(InventoryClosure $closure)
    {
        $filename = 'cierre_' . $closure->closure_number . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InventoryClosureExport($closure), $filename);
    }
}
