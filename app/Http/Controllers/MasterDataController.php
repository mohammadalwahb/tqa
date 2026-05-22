<?php

namespace App\Http\Controllers;

use App\Services\MasterData\MasterDataCsvExporter;
use App\Services\MasterData\MasterDataCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('master_data.index');
    }

    public function exportColleges(MasterDataCsvExporter $exporter): StreamedResponse
    {
        return $exporter->colleges();
    }

    public function exportDepartments(MasterDataCsvExporter $exporter): StreamedResponse
    {
        return $exporter->departments();
    }

    public function exportStaffFieldOptions(MasterDataCsvExporter $exporter): StreamedResponse
    {
        return $exporter->staffFieldOptions();
    }

    public function importColleges(Request $request, MasterDataCsvImporter $importer): RedirectResponse
    {
        return $this->runImport($request, fn () => $importer->importColleges($request->file('file')), $importer);
    }

    public function importDepartments(Request $request, MasterDataCsvImporter $importer): RedirectResponse
    {
        return $this->runImport($request, fn () => $importer->importDepartments($request->file('file')), $importer);
    }

    public function importStaffFieldOptions(Request $request, MasterDataCsvImporter $importer): RedirectResponse
    {
        return $this->runImport($request, fn () => $importer->importStaffFieldOptions($request->file('file')), $importer);
    }

    private function runImport(Request $request, callable $import, MasterDataCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $import();

        return redirect()
            ->route('master-data.index')
            ->with(
                empty($importer->errors) ? 'success' : 'error',
                sprintf(
                    'Import finished. Created: %d, Updated: %d.%s',
                    $importer->created,
                    $importer->updated,
                    empty($importer->errors) ? '' : ' Issues: ' . implode(' | ', array_slice($importer->errors, 0, 10))
                )
            );
    }
}
