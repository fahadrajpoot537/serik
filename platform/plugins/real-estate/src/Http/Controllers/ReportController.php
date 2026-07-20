<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    public function index(Request $request)
    {
        $this->pageTitle(trans('plugins/real-estate::reports.name'));

        $this
            ->breadcrumb()
            ->add(trans('plugins/real-estate::reports.name'), route('reports.index'));

        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/reports.js',
        ])
        ->addScripts(['raphael', 'morris'])
        ->addStyles(['morris']);

        // Get property statistics
        $propertyStats = $this->getPropertyStatistics();

        // Get project statistics
        $projectStats = $this->getProjectStatistics();

        // Get account statistics
        $accountStats = $this->getAccountStatistics();

        // Get recent transactions
        $recentTransactions = $this->getRecentTransactions();

        // Get property by type statistics
        $propertyByTypeStats = $this->getPropertyByTypeStatistics();

        // Get property by location statistics
        $propertyByLocationStats = $this->getPropertyByLocationStatistics();

        // Get monthly property statistics
        $monthlyPropertyStats = $this->getMonthlyPropertyStatistics();

        return view('plugins/real-estate::reports.index', compact(
            'propertyStats',
            'projectStats',
            'accountStats',
            'recentTransactions',
            'propertyByTypeStats',
            'propertyByLocationStats',
            'monthlyPropertyStats'
        ));
    }

    protected function getPropertyStatistics()
    {
        $activeCount = Property::query()->active()->count();
        $pendingCount = Property::query()->where('moderation_status', ModerationStatusEnum::PENDING)->count();
        $expiredCount = Property::query()->expired()->count();
        $totalCount = Property::query()->count();

        return [
            'active' => $activeCount,
            'pending' => $pendingCount,
            'expired' => $expiredCount,
            'total' => $totalCount,
        ];
    }

    protected function getProjectStatistics()
    {
        $totalProjects = Project::query()->count();
        $featuredProjects = Project::query()->where('is_featured', true)->count();

        return [
            'total' => $totalProjects,
            'featured' => $featuredProjects,
        ];
    }

    protected function getAccountStatistics()
    {
        $totalAccounts = Account::query()->count();
        $newAccountsThisMonth = Account::query()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return [
            'total' => $totalAccounts,
            'new_this_month' => $newAccountsThisMonth,
        ];
    }

    protected function getRecentTransactions()
    {
        return Transaction::query()
            ->with(['account', 'payment'])->latest()
            ->limit(10)
            ->get();
    }

    protected function getPropertyByTypeStatistics()
    {
        $stats = [];
        foreach (PropertyTypeEnum::labels() as $key => $label) {
            $count = Property::query()->where('type', $key)->count();
            if ($count > 0) {
                $stats[] = [
                    'label' => $label,
                    'value' => $count,
                ];
            }
        }

        return $stats;
    }

    protected function getPropertyByLocationStatistics()
    {
        if (! is_plugin_active('location')) {
            return [];
        }

        return DB::table('re_properties')
            ->join('cities', 're_properties.city_id', '=', 'cities.id')
            ->select('cities.name', DB::raw('count(*) as total'))
            ->groupBy('cities.name')->latest('total')
            ->limit(10)
            ->get();
    }

    protected function getMonthlyPropertyStatistics()
    {
        $result = [];
        $currentDate = Carbon::now();
        $startDate = Carbon::now()->startOfYear();

        while ($startDate->lte($currentDate)) {
            $month = $startDate->format('M');
            $year = $startDate->format('Y');

            $result[] = [
                'month' => $month,
                'year' => $year,
                'count' => Property::query()
                    ->whereMonth('created_at', $startDate->month)
                    ->whereYear('created_at', $startDate->year)
                    ->count(),
            ];

            $startDate->addMonth();
        }

        return $result;
    }
}
