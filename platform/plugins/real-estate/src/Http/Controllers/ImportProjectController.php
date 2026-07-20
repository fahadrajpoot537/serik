<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Importer\Importer;
use Botble\RealEstate\Importers\ProjectImporter;

class ImportProjectController extends ImportController
{
    protected function getImporter(): Importer
    {
        return ProjectImporter::make();
    }
}
