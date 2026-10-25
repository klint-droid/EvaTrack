@extends('exports.layout')

@section('content')

<style>
    .event-headline-banner {
        background-color: #1e3a5f;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 4px;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        margin-bottom: 10px;
        display: inline-block;
    }
    .summary-text-box {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-left: 4px solid #1e3a5f;
        padding: 8px 14px;
        margin-bottom: 12px;
        font-size: 8.5px;
    }
    .summary-title {
        font-size: 9px;
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        padding-bottom: 3px;
        border-bottom: 1px solid #e2e8f0;
    }
    .summary-bullets {
        margin: 0;
        padding-left: 16px;
        color: #334155;
        line-height: 1.6;
    }
    .summary-bullets li {
        margin-bottom: 2px;
    }
    .summary-bullets li strong {
        color: #0f172a;
    }
    .section-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #475569;
        padding: 4px 0 2px 0;
        border-bottom: 1px solid #cbd5e1;
        margin-bottom: 6px;
    }
    table.data-table tr.totals-row td {
        background-color: #1e293b;
        color: #f1f5f9;
        font-weight: bold;
        font-size: 8px;
        border: 1px solid #334155;
    }
    table.data-table tr.totals-row td.text-center { text-align: center; }
</style>

{{-- ── Event Headline Banner ──────────────────────────────── --}}
@if(!empty($meta['event']))
<div class="event-headline-banner">
    DISASTER EVENT: {{ strtoupper($meta['event']) }}
</div>
@endif

{{-- ── Text-Only Statistical Executive Summary ────────────── --}}
<div class="summary-text-box">
    <div class="summary-title">Executive Evacuation Statistics Summary</div>
    <ul class="summary-bullets">
        <li><strong>Total Evacuated Families:</strong> {{ number_format($summaryStats['totalHouseholds'] ?? 0) }} families / households registered</li>
        <li><strong>Total Evacuated Individuals:</strong> {{ number_format($summaryStats['totalIndividuals'] ?? count($rows)) }} individual members listed in master roster</li>
        <li><strong>Sex Disaggregation:</strong> {{ number_format($summaryStats['totalMale'] ?? 0) }} Male &bull; {{ number_format($summaryStats['totalFemale'] ?? 0) }} Female</li>
        <li><strong>Age Group Demographics:</strong> {{ number_format($summaryStats['totalChildren'] ?? 0) }} Children (0–12 yrs) &bull; {{ number_format($summaryStats['totalYouth'] ?? 0) }} Youth (13–17 yrs) &bull; {{ number_format($summaryStats['totalAdults'] ?? 0) }} Adults (18–59 yrs) &bull; {{ number_format($summaryStats['totalSenior'] ?? 0) }} Senior Citizens (60+ yrs)</li>
        <li><strong>Vulnerable & Special Needs Sectors:</strong> {{ number_format($summaryStats['totalPwd'] ?? 0) }} Persons with Disability (PWD) &bull; {{ number_format($summaryStats['totalPregnant'] ?? 0) }} Pregnant / Lactating &bull; {{ number_format($summaryStats['totalIndigenous'] ?? 0) }} Indigenous Peoples (IP)</li>
    </ul>
</div>

{{-- ── Master List Data Table (All Evacuated Members) ────────── --}}
<div class="section-label">Master List of Evacuated Members (Individual Member Roster)</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 3%;">No.</th>
            <th style="width: 8%;">Household ID</th>
            <th style="width: 9%;">Family Name</th>
            <th style="width: 14%;">Member Full Name</th>
            <th style="width: 8%;">Relationship</th>
            <th style="width: 5%;" class="text-center">Sex</th>
            <th style="width: 4%;" class="text-center">Age</th>
            <th style="width: 7%;" class="text-center">Birthdate</th>
            <th style="width: 12%;">Special Needs / Sector</th>
            <th style="width: 11%;">Evacuation Center</th>
            <th style="width: 6%;">Room / Unit</th>
            <th style="width: 8%;">Origin Address</th>
            <th style="width: 5%;">Date Admitted</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row[0] }}</td>
            <td><code style="font-size: 7.5px;">{{ $row[2] }}</code></td>
            <td class="font-bold">{{ $row[3] }}</td>
            <td class="font-bold" style="color: #1e3a5f;">{{ $row[4] }}</td>
            <td>{{ $row[5] }}</td>
            <td class="text-center">{{ $row[6] }}</td>
            <td class="text-center">{{ $row[7] }}</td>
            <td class="text-center" style="font-size: 7.5px;">{{ $row[8] }}</td>
            <td>
                @if($row[9] !== 'None' && $row[9] !== '—')
                    <span style="color: #be123c; font-weight: bold;">{{ $row[9] }}</span>
                @else
                    <span style="color: #94a3b8;">None</span>
                @endif
            </td>
            <td>{{ $row[10] }}</td>
            <td>{{ $row[11] }}</td>
            <td style="font-size: 7.5px;">{{ $row[12] }}</td>
            <td style="font-size: 7.5px;">{{ $row[14] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="13" class="text-center" style="padding: 20px; color: #94a3b8;">
                No evacuated member records match the selected filter criteria.
            </td>
        </tr>
        @endforelse

        @if(count($rows) > 0)
        <tr class="totals-row">
            <td colspan="5" style="padding: 6px 8px; font-size: 8px;">
                TOTAL REGISTERED EVACUEES: {{ count($rows) }} {{ Str::plural('Member', count($rows)) }} (across {{ $summaryStats['totalHouseholds'] ?? 0 }} {{ Str::plural('Family', $summaryStats['totalHouseholds'] ?? 0) }})
            </td>
            <td class="text-center">{{ $summaryStats['totalMale'] ?? 0 }} M / {{ $summaryStats['totalFemale'] ?? 0 }} F</td>
            <td colspan="7"></td>
        </tr>
        @endif
    </tbody>
</table>

@endsection
