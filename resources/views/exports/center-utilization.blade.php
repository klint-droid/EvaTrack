@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th>Evacuation Center</th>
            <th>Location Address</th>
            <th class="text-right">Total Capacity</th>
            <th class="text-right">Current Occupants</th>
            <th class="text-right">Available Slots</th>
            <th class="text-right">Utilization Rate</th>
            <th class="text-right">Families</th>
            <th class="text-right">Total Units</th>
            <th class="text-right">Occupied Units</th>
            <th class="text-right">Available Units</th>
            <th>Operational Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td class="font-bold">{{ $row[0] }}</td>
            <td>{{ $row[1] }}</td>
            <td class="text-right">{{ number_format($row[2]) }}</td>
            <td class="text-right font-bold">{{ number_format($row[3]) }}</td>
            <td class="text-right">{{ number_format($row[4]) }}</td>
            <td class="text-right font-bold">{{ $row[5] }}</td>
            <td class="text-right">{{ number_format($row[6]) }}</td>
            <td class="text-right">{{ number_format($row[7]) }}</td>
            <td class="text-right">{{ number_format($row[8]) }}</td>
            <td class="text-right">{{ number_format($row[9]) }}</td>
            <td>
                @if($row[10] === 'Overcapacity')
                    <span class="badge badge-critical">OVERCAPACITY</span>
                @elseif($row[10] === 'Near Capacity')
                    <span class="badge badge-warning">NEAR CAPACITY</span>
                @else
                    <span class="badge badge-optimal">OPTIMAL LOAD</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">
                No evacuation center metrics found for the selected scope.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
