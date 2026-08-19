@extends('exports.layout')

@section('content')

{{-- ── Summary KPI strip ──────────────────────────────────────── --}}
@php
    $totalHouseholds = count($rows);
    $totalMembers    = array_sum(array_column($rows, 7));
    $totalMale       = array_sum(array_column($rows, 8));
    $totalFemale     = array_sum(array_column($rows, 9));
    $totalChildren   = array_sum(array_column($rows, 10));
    $totalYouth      = array_sum(array_column($rows, 11));
    $totalAdults     = array_sum(array_column($rows, 12));
    $totalSenior     = array_sum(array_column($rows, 13));
    $totalPwd        = array_sum(array_column($rows, 14));
    $totalPregnant   = array_sum(array_column($rows, 15));
    $totalIndigenous = array_sum(array_column($rows, 16));
@endphp

<style>
    .kpi-strip { width: 100%; margin-bottom: 14px; }
    .kpi-strip td {
        text-align: center;
        padding: 8px 6px;
        border: 1px solid #bfdbfe;
        background-color: #eff6ff;
        border-radius: 4px;
        font-size: 9px;
    }
    .kpi-strip .kpi-num  { font-size: 16px; font-weight: 800; color: #1d4ed8; display: block; }
    .kpi-strip .kpi-lbl  { color: #1e40af; font-weight: 600; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px; }
    .kpi-strip .kpi-warn { background-color: #fef9c3; border-color: #fde047; }
    .kpi-strip .kpi-warn .kpi-num { color: #92400e; }
    .kpi-strip .kpi-warn .kpi-lbl { color: #78350f; }
    .kpi-strip .kpi-rose { background-color: #fff1f2; border-color: #fca5a5; }
    .kpi-strip .kpi-rose .kpi-num { color: #be123c; }
    .kpi-strip .kpi-rose .kpi-lbl { color: #9f1239; }
    .section-label {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #94a3b8;
        padding: 4px 0 2px 0;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    table.data-table th.th-group {
        background-color: #1e3a5f;
        color: #fff;
        text-align: center;
        padding: 4px 6px;
        font-size: 8px;
        letter-spacing: 0.5px;
        border: 1px solid #1e3a5f;
    }
    table.data-table tr.totals-row td {
        background-color: #1e293b;
        color: #f1f5f9;
        font-weight: bold;
        font-size: 8.5px;
        border: 1px solid #334155;
    }
    table.data-table tr.totals-row td.text-center { text-align: center; }
</style>

{{-- Summary KPI strip --}}
<div class="section-label">Evacuation Summary</div>
<table class="kpi-strip" cellspacing="3" cellpadding="0">
    <tr>
        <td>
            <span class="kpi-num">{{ number_format($totalHouseholds) }}</span>
            <span class="kpi-lbl">Total Families</span>
        </td>
        <td>
            <span class="kpi-num">{{ number_format($totalMembers) }}</span>
            <span class="kpi-lbl">Total Individuals</span>
        </td>
        <td>
            <span class="kpi-num">{{ number_format($totalMale) }}</span>
            <span class="kpi-lbl">Male</span>
        </td>
        <td>
            <span class="kpi-num">{{ number_format($totalFemale) }}</span>
            <span class="kpi-lbl">Female</span>
        </td>
        <td>
            <span class="kpi-num">{{ number_format($totalChildren + $totalYouth) }}</span>
            <span class="kpi-lbl">Minors (0–17)</span>
        </td>
        <td>
            <span class="kpi-num">{{ number_format($totalSenior) }}</span>
            <span class="kpi-lbl">Senior Citizens</span>
        </td>
        <td class="kpi-warn">
            <span class="kpi-num">{{ number_format($totalPwd) }}</span>
            <span class="kpi-lbl">PWD</span>
        </td>
        <td class="kpi-rose">
            <span class="kpi-num">{{ number_format($totalPregnant) }}</span>
            <span class="kpi-lbl">Pregnant</span>
        </td>
        <td class="kpi-warn">
            <span class="kpi-num">{{ number_format($totalIndigenous) }}</span>
            <span class="kpi-lbl">Indigenous</span>
        </td>
    </tr>
</table>

{{-- Main data table --}}
<div class="section-label" style="margin-top: 10px;">Master List of Evacuated Families</div>

<table class="data-table">
    <thead>
        {{-- Column group header row --}}
        <tr>
            <th class="th-group" colspan="2">Reference</th>
            <th class="th-group" colspan="3">Household Identification</th>
            <th class="th-group" colspan="8">Population Breakdown</th>
            <th class="th-group" colspan="3">Vulnerable Groups</th>
            <th class="th-group" colspan="2">Accommodation</th>
        </tr>
        {{-- Actual column headers --}}
        <tr>
            <th style="width: 3%;">No.</th>
            <th style="width: 8%;">Event</th>
            <th style="width: 10%;">Center</th>
            <th style="width: 8%;">Household ID</th>
            <th style="width: 10%;">Family Name</th>
            <th style="width: 8%;">Contact No.</th>
            <th style="width: 13%;">Home Address</th>
            <th style="width: 4%;" class="text-center">Total</th>
            <th style="width: 3%;" class="text-center">Male</th>
            <th style="width: 3%;" class="text-center">Female</th>
            <th style="width: 3%;" class="text-center">Children (0–12)</th>
            <th style="width: 3%;" class="text-center">Youth (13–17)</th>
            <th style="width: 3%;" class="text-center">Adults (18–59)</th>
            <th style="width: 3%;" class="text-center">Senior (60+)</th>
            <th style="width: 3%;" class="text-center">PWD</th>
            <th style="width: 3%;" class="text-center">Pregnant</th>
            <th style="width: 3%;" class="text-center">Indigenous</th>
            <th style="width: 6%;">Unit / Room</th>
            <th style="width: 10%;">Date Admitted</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row[0] }}</td>
            <td>{{ $row[1] }}</td>
            <td>{{ $row[2] }}</td>
            <td><code style="font-size: 7.5px;">{{ $row[3] }}</code></td>
            <td class="font-bold">{{ $row[4] }}</td>
            <td>{{ $row[5] }}</td>
            <td style="font-size: 8px;">{{ $row[6] }}</td>
            <td class="text-center font-bold">{{ $row[7] }}</td>
            <td class="text-center">{{ $row[8] ?: '—' }}</td>
            <td class="text-center">{{ $row[9] ?: '—' }}</td>
            <td class="text-center">{{ $row[10] ?: '—' }}</td>
            <td class="text-center">{{ $row[11] ?: '—' }}</td>
            <td class="text-center">{{ $row[12] ?: '—' }}</td>
            <td class="text-center">{{ $row[13] ?: '—' }}</td>
            <td class="text-center {{ $row[14] > 0 ? 'font-bold' : '' }}" style="{{ $row[14] > 0 ? 'color:#1d4ed8;' : '' }}">
                {{ $row[14] > 0 ? $row[14] : '—' }}
            </td>
            <td class="text-center {{ $row[15] > 0 ? 'font-bold' : '' }}" style="{{ $row[15] > 0 ? 'color:#be123c;' : '' }}">
                {{ $row[15] > 0 ? $row[15] : '—' }}
            </td>
            <td class="text-center {{ $row[16] > 0 ? 'font-bold' : '' }}">
                {{ $row[16] > 0 ? $row[16] : '—' }}
            </td>
            <td style="font-size: 8px;">{{ $row[17] }}</td>
            <td style="font-size: 8px;">{{ $row[18] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="19" class="text-center" style="padding: 20px; color: #94a3b8;">
                No evacuated family records match the selected filter criteria.
            </td>
        </tr>
        @endforelse

        {{-- TOTALS row --}}
        @if(count($rows) > 0)
        <tr class="totals-row">
            <td colspan="7" style="padding: 6px 8px; font-size: 8.5px;">TOTALS — {{ $totalHouseholds }} {{ Str::plural('Family', $totalHouseholds) }}</td>
            <td class="text-center">{{ $totalMembers }}</td>
            <td class="text-center">{{ $totalMale }}</td>
            <td class="text-center">{{ $totalFemale }}</td>
            <td class="text-center">{{ $totalChildren }}</td>
            <td class="text-center">{{ $totalYouth }}</td>
            <td class="text-center">{{ $totalAdults }}</td>
            <td class="text-center">{{ $totalSenior }}</td>
            <td class="text-center">{{ $totalPwd }}</td>
            <td class="text-center">{{ $totalPregnant }}</td>
            <td class="text-center">{{ $totalIndigenous }}</td>
            <td colspan="2"></td>
        </tr>
        @endif
    </tbody>
</table>

@endsection
