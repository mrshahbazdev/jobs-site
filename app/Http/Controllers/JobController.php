<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use App\Models\HomeBlock;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('jobListings')->get();
        $cities = City::all();
        
        // Get unique filter values from DB
        $jobTypes = JobListing::whereNotNull('job_type')->where('is_active', true)->distinct()->pluck('job_type');
        $experiences = JobListing::whereNotNull('experience')->where('is_active', true)->distinct()->pluck('experience');

        $query = JobListing::where('is_active', true);

        if ($request->filled('job_type')) {
            $query->whereIn('job_type', (array) $request->job_type);
        }

        if ($request->filled('experience')) {
            $query->whereIn('experience', (array) $request->experience);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('salary_min')) {
            $query->where(function($q) use ($request) {
                $q->where('salary_min', '>=', $request->salary_min)
                  ->orWhereNull('salary_min');
            });
        }

        if ($request->filled('salary_max')) {
            $query->where(function($q) use ($request) {
                $q->where('salary_max', '<=', $request->salary_max)
                  ->orWhereNull('salary_max');
            });
        }

        $featuredJobs = JobListing::where('is_active', true)
            ->where(fn($q) => $q->where('is_featured', true)->orWhere('is_premium', true))
            ->latest()
            ->take(6)
            ->get();

        $latestJobs = $query->latest()->paginate(10)->withQueryString();

        $landingGroups = \App\Models\LandingGroup::active()->ordered()->with(['links' => fn($q) => $q->active()->ordered()])->get();
        $homeBlocks = \App\Models\HomeBlock::active()->where('page_slug', 'home')->ordered()->get();

        return view('home', compact('categories', 'cities', 'featuredJobs', 'latestJobs', 'jobTypes', 'experiences', 'landingGroups', 'homeBlocks'));
    }

    public function show($slug)
    {
        $job = JobListing::where('slug', $slug)->with(['category', 'city'])->firstOrFail();
        $relatedJobs = JobListing::where('category_id', $job->category_id)
            ->where('id', '!=', $job->id)
            ->where('is_active', true)
            ->latest()
            ->take(5)
            ->get();
            
        return view('jobs.show', compact('job', 'relatedJobs'));
    }

    public function category(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $categories = Category::all();
        $cities = City::all();

        // Get unique filter values from DB
        $jobTypes = JobListing::whereNotNull('job_type')->where('is_active', true)->distinct()->pluck('job_type');
        $experiences = JobListing::whereNotNull('experience')->where('is_active', true)->distinct()->pluck('experience');

        $query = JobListing::where('category_id', $category->id)->where('is_active', true);

        if ($request->filled('job_type')) {
            $query->whereIn('job_type', (array) $request->job_type);
        }

        if ($request->filled('experience')) {
            $query->whereIn('experience', (array) $request->experience);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        $jobs = $query->latest()->paginate(15)->withQueryString();

        return view('categories.show', compact('category', 'jobs', 'categories', 'cities', 'jobTypes', 'experiences'));
    }

    public function categories()
    {
        $categories = Category::withCount('jobListings')->get();
        $cities = City::all();
        return view('categories.index', compact('categories', 'cities'));
    }

    public function city(Request $request, $slug)
    {
        $city = City::where('slug', $slug)->firstOrFail();
        $categories = Category::all();
        $cities = City::all();

        // Get unique filter values from DB
        $jobTypes = JobListing::whereNotNull('job_type')->where('is_active', true)->distinct()->pluck('job_type');
        $experiences = JobListing::whereNotNull('experience')->where('is_active', true)->distinct()->pluck('experience');

        $query = JobListing::where('city_id', $city->id)->where('is_active', true);

        if ($request->filled('job_type')) {
            $query->whereIn('job_type', (array) $request->job_type);
        }

        if ($request->filled('experience')) {
            $query->whereIn('experience', (array) $request->experience);
        }

        $jobs = $query->latest()->paginate(15)->withQueryString();

        return view('cities.show', compact('city', 'jobs', 'categories', 'cities', 'jobTypes', 'experiences'));
    }

    public function search(Request $request)
    {
        $queryText = $request->input('q');
        $aiFilters = [];

        if (strlen($queryText) > 10) {
            $aiFilters = GeminiService::parseSearchQuery($queryText);
        }

        $query = JobListing::where('is_active', true);

        if (!empty($aiFilters)) {
            if (!empty($aiFilters['keywords'])) {
                $query->where(function($q) use ($aiFilters) {
                    $q->where('title', 'like', "%{$aiFilters['keywords']}%")
                      ->orWhere('description_html', 'like', "%{$aiFilters['keywords']}%");
                });
            }

            if (!empty($aiFilters['city'])) {
                $query->whereHas('city', function($q) use ($aiFilters) {
                    $q->where('name', 'like', "%{$aiFilters['city']}%");
                });
            }

            if (!empty($aiFilters['experience'])) {
                $query->where('experience', 'like', "%{$aiFilters['experience']}%");
            }

            if (!empty($aiFilters['job_type'])) {
                $query->where('job_type', 'like', "%{$aiFilters['job_type']}%");
            }
        } else {
            $query->where(function($q) use ($queryText) {
                $q->where('title', 'like', "%{$queryText}%")
                  ->orWhere('description_html', 'like', "%{$queryText}%")
                  ->orWhere('department', 'like', "%{$queryText}%");
            });
        }

        $jobs = $query->latest()->paginate(15)->withQueryString();
            
        $categories = Category::all();
        $cities = City::all();
        $query = $queryText;

        return view('search.results', compact('jobs', 'query', 'categories', 'cities', 'aiFilters'));
    }

    public function education(Request $request, $education)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('education', $education)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Jobs for " . ucfirst($education) . " Candidates";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function newspaper(Request $request, $newspaper)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('newspaper', $newspaper)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = ucfirst($newspaper) . " Newspaper Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function department(Request $request, $department)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('department', 'like', "%{$department}%")
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = ucfirst($department) . " Department Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function province(Request $request, $province)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('province', $province)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Latest Jobs in " . ucfirst($province);
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function gender(Request $request, $gender)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('gender', $gender)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Jobs for " . ucfirst($gender) . " Candidates";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function bps(Request $request, $scale)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('bps_scale', $scale)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Government Jobs of " . strtoupper($scale) . " Scale";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function today(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::whereDate('created_at', now()->toDateString())
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Today's New Jobs (" . now()->format('M d, Y') . ")";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function expiring(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::whereBetween('deadline', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Jobs Expiring Soon (Apply Now!)";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function degree(Request $request, $degree)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('qualification_degree', 'like', "%{$degree}%")
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = strtoupper($degree) . " Degree Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function type(Request $request, $type)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('job_type', 'like', "%{$type}%")
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = ucfirst($type) . " Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function salaryRange(Request $request, $bucket)
    {
        $categories = Category::all();
        $cities = City::all();
        $query = JobListing::where('is_active', true);

        if ($bucket === 'high-salary') {
            $query->where('salary_min', '>=', 100000);
            $title = "High Salary Jobs (PKR 100,000+)";
        } elseif ($bucket === 'mid-range') {
            $query->whereBetween('salary_min', [50000, 100000]);
            $title = "Mid-Range Salary Jobs (50k - 100k)";
        } else {
            $query->where('salary_max', '<=', 50000);
            $title = "Entry Level Jobs (Under 50k)";
        }
        
        $jobs = $query->latest()->paginate(15);
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function quota(Request $request, $quota_type)
    {
        $categories = Category::all();
        $cities = City::all();
        $query = JobListing::where('is_active', true);

        if ($quota_type === 'special-persons') {
            $query->where('is_special_quota', true);
            $title = "Jobs for Special Persons (Disabled Quota)";
        } else {
            $query->where('is_minority_quota', true);
            $title = "Minority Quota Jobs";
        }
        
        $jobs = $query->latest()->paginate(15);
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function testingService(Request $request, $service)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('testing_service', $service)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = strtoupper($service) . " Test Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function country(Request $request, $country)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('country', $country)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = "Latest Overseas Jobs in " . ucfirst($country);
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function sector(Request $request, $sector)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('sector', $sector)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = ucfirst($sector) . " Sector Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function role(Request $request, $role)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('job_role', 'like', "%{$role}%")
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = ucfirst($role) . " Jobs in Pakistan";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function council(Request $request, $council)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('registration_council', $council)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $title = strtoupper($council) . " Registered Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function archive(Request $request, $year, $month)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        
        $date = \DateTime::createFromFormat('!m', $month);
        $monthName = $date->format('F');
        $title = "Jobs in " . $monthName . " " . $year;
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function allLists()
    {
        $allListsBlocks = HomeBlock::active()->where('page_slug', 'all-lists')->ordered()->get();
        return view('jobs.all_lists', compact('allListsBlocks'));
    }

    public function walkin(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('has_walkin_interview', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Walk-in Interview Jobs in Pakistan";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function whatsappJobs(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('is_whatsapp_apply', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "WhatsApp Apply Jobs (Easy Application)";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function remote(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('is_remote', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Remote / Work From Home Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function freshGraduates(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('experience', 'like', '%Fresh%')
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Jobs for Fresh Graduates";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function retiredArmy(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('is_retired_army', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Jobs for Retired Army Officers";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function studentJobs(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('is_student_friendly', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Jobs for Students (Part-time)";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function industrial(Request $request, $slug)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('sub_sector', $slug)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = $slug . " Industry Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function contract(Request $request, $type)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('contract_type', $type)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = $type . " Basis Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function accommodation(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('has_accommodation', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Jobs with Free Accommodation / Hostel";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function transport(Request $request)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('has_transport', true)
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = "Jobs with Free Transport (Pick & Drop)";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    public function skill(Request $request, $skill)
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::where('skills', 'like', "%{$skill}%")
            ->where('is_active', true)
            ->latest()
            ->paginate(15);
        $title = $skill . " Skills Jobs";
        return view('jobs.list', compact('jobs', 'categories', 'cities', 'title'));
    }

    /**
     * Display the AMP version of a job listing.
     */
    public function ampShow($slug)
    {
        $job = JobListing::where('slug', $slug)->with(['category', 'city'])->firstOrFail();
        $relatedJobs = JobListing::where('category_id', $job->category_id)
            ->where('id', '!=', $job->id)
            ->where('is_active', true)
            ->latest()
            ->take(3)
            ->get();
            
        return view('jobs.amp', compact('job', 'relatedJobs'));
    }

    /**
     * Display the Web Story (AMP Story) version of a job listing.
     */
    public function storyShow($slug)
    {
        $job = JobListing::where('slug', $slug)->with(['category', 'city'])->firstOrFail();
        
        return view('jobs.story', compact('job'));
    }
}
