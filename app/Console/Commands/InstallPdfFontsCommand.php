<?php

namespace App\Console\Commands;

use App\Services\Pdf\DomPdfFontRegistrar;
use Illuminate\Console\Command;

class InstallPdfFontsCommand extends Command
{
    protected $signature = 'pdf:install-fonts';

    protected $description = 'Install DomPDF font metrics (Arabic) into storage/fonts';

    public function handle(): int
    {
        if (! DomPdfFontRegistrar::arabicFontSourcePath()) {
            $this->error('Missing resources/fonts/NotoSansArabic-Regular.ttf');

            return self::FAILURE;
        }

        DomPdfFontRegistrar::installArabicFontMetrics();

        $this->info('PDF fonts installed in: ' . storage_path('fonts'));
        $this->line('Ensure this directory is writable by the web server (chmod 775).');

        return self::SUCCESS;
    }
}
