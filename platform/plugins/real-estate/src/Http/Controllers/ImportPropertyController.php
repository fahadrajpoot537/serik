<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Importer\Importer;
use Botble\RealEstate\Importers\PropertyImporter;

class ImportPropertyController extends ImportController
{
    protected function getImporter(): Importer
    {
        return PropertyImporter::make();
    }
}
