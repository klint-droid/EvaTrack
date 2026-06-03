@extends('exports.layout')

@section('content')
<style>
    .summary-card-container {
        width: 100%;
        margin-top: 20px;
    }
    .summary-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 12px;
        width: 48%;
        display: inline-block;
        vertical-align: top;
        box-sizing: border-box;
    }
    .summary-card:first-child {
        margin-right: 3%;
    }
    .summary-card h3 {
        margin: 0 0 10px 0;
        font-size: 11px;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 5px;
    }
    .metric-row {
        width: 100%;
        padding: 4px 0;
        font-size: 11px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .metric-row:last-child {
        border-bottom: none;
    }
    .metric-label {
        display: inline-block;
        width: 60%;
        color: #64748b;
    }
    .metric-value {
        display: inline-block;
        width: 38%;
        text-align: right;
        font-weight: bold;
        color: #1e293b;
    }
    .kpi-row {
        width: 100%;
        margin-bottom: 15px;
    }
    .kpi-card {
        background-color: #eff6ff;
        border-left: 4px solid #2563eb;
        border-top: 1px solid #bfdbfe;
        border-right: 1px solid #bfdbfe;
        border-bottom: 1px solid #bfdbfe;
        border-radius: 4px;
        padding: 10px 15px;
        display: inline-block;
        width: 48%;
        box-sizing: border-box;
    }
    .kpi-card:first-child {
        margin-right: 3%;
    }
    .kpi-title {
        font-size: 9px;
        color: #1e40af;
        text-transform: uppercase;
        font-weight: bold;
    }
    .kpi-value {
        font-size: 20px;
        font-weight: 800;
        color: #1d4ed8;
        margin-top: 3px;
    }
</style>

<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-title">Total Families Evacuated</div>
        <div class="kpi-value">{{ number_format($totalHouseholds) }}</div>
    </div>
    <div class="kpi-card" style="background-color: #ecfdf5; border-left-color: #10b981; border-top-color: #a7f3d0; border-right-color: #a7f3d0; border-bottom-color: #a7f3d0;">
        <div class="kpi-title" style="color: #065f46;">Total Individuals Evacuated</div>
        <div class="kpi-value" style="color: #047857;">{{ number_format($totalIndividuals) }}</div>
    </div>
</div>

<div class="summary-card-container">
    <div class="summary-card">
        <h3>Gender Breakdown</h3>
        <div class="metric-row">
            <span class="metric-label">Male</span>
            <span class="metric-value">
                {{ number_format($male) }} 
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($male / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        <div class="metric-row">
            <span class="metric-label">Female</span>
            <span class="metric-value">
                {{ number_format($female) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($female / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        
        <h3 style="margin-top: 20px;">Vulnerable Group Registers</h3>
        @forelse($vulnCounts as $label => $count)
        <div class="metric-row">
            <span class="metric-label">{{ $label }}</span>
            <span class="metric-value">
                {{ number_format($count) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($count / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        @empty
        <div class="text-center" style="padding: 15px; color: #94a3b8; font-size: 10px;">
            No vulnerable groups flagged in this dataset.
        </div>
        @endforelse
    </div>

    <div class="summary-card">
        <h3>Age Group Distribution</h3>
        <div class="metric-row">
            <span class="metric-label">Children (0-12 years)</span>
            <span class="metric-value">
                {{ number_format($children) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($children / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        <div class="metric-row">
            <span class="metric-label">Youth (13-17 years)</span>
            <span class="metric-value">
                {{ number_format($youth) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($youth / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        <div class="metric-row">
            <span class="metric-label">Adults (18-59 years)</span>
            <span class="metric-value">
                {{ number_format($adults) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($adults / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
        <div class="metric-row">
            <span class="metric-label">Senior Citizens (60+ years)</span>
            <span class="metric-value">
                {{ number_format($elderly) }}
                <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">
                    ({{ $totalIndividuals > 0 ? round(($elderly / $totalIndividuals) * 100, 1) : 0 }}%)
                </span>
            </span>
        </div>
    </div>
</div>
@endsection
