<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $course->title ?? 'Course' }} - {{ $contact['site_name'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; margin:0; }
        .page { padding: 28px; }
        .header { display:flex; align-items:center; gap:12px; border-bottom:1px solid #ddd; padding-bottom:12px; margin-bottom:18px }
        .logo { width:120px }
        .contact { font-size:12px; color:#555 }
        .title { font-size:20px; font-weight:700; margin-bottom:6px }
        .section { margin-bottom:14px }
        .schedules { width:100%; border-collapse: collapse }
        .schedules th, .schedules td { padding:8px; border:1px solid #e6e6e6; font-size:12px }
        .schedules th { background:#f7f7f7; text-align:left }
        .intro, .body { font-size:13px; line-height:1.45 }
    </style>
</head>
<body>
    <div class="page">
    <div class="header">
        <div class="logo">
            <img src="{{ public_path('images/logo.png') ? asset('images/logo.png') : 'https://datastatresearch.org/images/logo.png' }}" alt="{{ $contact['site_name'] }}" style="max-width:120px">
        </div>
        <div>
            <div class="title">{{ $course->title }}</div>
            <div class="contact">
                {{ $contact['site_name'] }}
                @if(!empty($contact['address']))<div style="margin-top:6px; font-size:12px; color:#555">{!! nl2br(e($contact['address'])) !!}</div>@endif
                <div style="margin-top:6px; font-size:12px; color:#555">
                    @if(!empty($contact['phones']) && is_array($contact['phones']))
                        @foreach($contact['phones'] as $phone)
                            {{ $phone }}@if(!$loop->last) &nbsp;|&nbsp;@endif
                        @endforeach
                    @elseif(!empty($contact['phone']))
                        {{ $contact['phone'] }}
                    @endif
                </div>
                <div style="margin-top:4px; font-size:12px; color:#555">{{ $contact['email'] }} @if(!empty($contact['website'])) | {{ $contact['website'] }} @endif</div>
            </div>
        </div>
    </div>

    <div class="section intro">
        {!! $course->introduction ?? '<em>No introduction provided.</em>' !!}
    </div>

    <div class="section body">
        {!! $course->body ?? '<em>No course content provided.</em>' !!}
    </div>

    <div class="section">
        <h3 style="margin-bottom:8px">Upcoming schedules</h3>
        @if($schedules->isEmpty())
            <div style="font-size:12px;color:#666">No schedules found up to the set date.</div>
        @else
            <table class="schedules">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Fee (USD)</th>
                        <th>Fee (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $s)
                    <tr>
                        <td>{{ $s->start ? (is_string($s->start) ? \Carbon\Carbon::parse($s->start)->format('M d, Y') : $s->start->format('M d, Y')) : 'TBA' }}@if($s->end) - {{ is_string($s->end) ? \Carbon\Carbon::parse($s->end)->format('M d, Y') : $s->end->format('M d, Y') }}@endif</td>
                        <td>{{ $s->location }}</td>
                        <td>${{ number_format($s->course_fee_usd ?? 0, 2) }}</td>
                        <td>{{ number_format($s->course_fee_ksh ?? 0, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    </div>
    </div>
</body>
</html>
