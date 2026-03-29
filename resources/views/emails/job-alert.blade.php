<x-mail::message>
# 🚀 New Jobs Matched for You!

Hello,

We found some new job listings that match your preferences on **JobsPic**.

<x-mail::table>
| Job Title | Location | Action |
| :--- | :--- | :--- |
@foreach($jobs as $job)
| **{{ $job->title }}** | {{ $job->city->name }} | [View Job]({{ url('/jobs/'.$job->slug) }}) |
@endforeach
</x-mail::table>

<x-mail::button :url="url('/')">
Browse All Jobs
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} team

<small>If you wish to unsubscribe, please visit our site.</small>
</x-mail::message>
