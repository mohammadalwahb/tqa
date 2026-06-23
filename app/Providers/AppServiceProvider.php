<?php

namespace App\Providers;

use App\Models\CertificateTemplate;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Policies\CertificateTemplatePolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\StaffMemberPolicy;
use Illuminate\Pagination\Paginator;
use App\Services\Pdf\DomPdfFontRegistrar;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        DomPdfFontRegistrar::ensureFontDirectories();

        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(CertificateTemplate::class, CertificateTemplatePolicy::class);
        Gate::policy(StaffMember::class, StaffMemberPolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        Blade::directive('pdfText', function (string $expression): string {
            return "<?php echo \\App\\Support\\PdfTextHelper::present({$expression}); ?>";
        });
    }
}
