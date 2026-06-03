@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 3%;">No.</th>
            <th style="width: 8%;">Event</th>
            <th style="width: 10%;">Center</th>
            <th style="width: 8%;">HH ID</th>
            <th style="width: 10%;">Family Head</th>
            <th style="width: 8%;">Contact</th>
            <th style="width: 14%;">Home Address</th>
            <th style="width: 4%;">Mbrs</th>
            <th style="width: 3%;">M</th>
            <th style="width: 3%;">F</th>
            <th style="width: 3%;">Chd</th>
            <th style="width: 3%;">Yth</th>
            <th style="width: 3%;">Adlt</th>
            <th style="width: 3%;">Snr</th>
            <th style="width: 3%;">PWD</th>
            <th style="width: 3%;">Prg</th>
            <th style="width: 3%;">Indg</th>
            <th style="width: 6%;">Unit</th>
            <th style="width: 10%;">Admitted At</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row[0] }}</td>
            <td>{{ $row[1] }}</td>
            <td>{{ $row[2] }}</td>
            <td><code>{{ $row[3] }}</code></td>
            <td class="font-bold">{{ $row[4] }}</td>
            <td>{{ $row[5] }}</td>
            <td>{{ $row[6] }}</td>
            <td class="text-center font-bold">{{ $row[7] }}</td>
            <td class="text-center">{{ $row[8] }}</td>
            <td class="text-center">{{ $row[9] }}</td>
            <td class="text-center">{{ $row[10] }}</td>
            <td class="text-center">{{ $row[11] }}</td>
            <td class="text-center">{{ $row[12] }}</td>
            <td class="text-center">{{ $row[13] }}</td>
            <td class="text-center font-bold">{{ $row[14] > 0 ? $row[14] : '-' }}</td>
            <td class="text-center font-bold">{{ $row[15] > 0 ? $row[15] : '-' }}</td>
            <td class="text-center font-bold">{{ $row[16] > 0 ? $row[16] : '-' }}</td>
            <td>{{ $row[17] }}</td>
            <td>{{ $row[18] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="19" class="text-center" style="padding: 20px; color: #94a3b8;">
                No evacuated family records match the selected filter criteria.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
