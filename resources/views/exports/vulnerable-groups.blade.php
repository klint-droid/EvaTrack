@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            <th>Evacuation Center</th>
            <th>Family Head Name</th>
            <th>Vulnerable Member Name</th>
            <th class="text-center">Age</th>
            <th>Gender</th>
            <th>Vulnerability Type / Flags</th>
            <th>Contact Number</th>
            <th>Unit Assignment</th>
            <th>Origin address</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td class="font-bold">{{ $row[0] }}</td>
            <td>{{ $row[1] }}</td>
            <td class="font-bold">{{ $row[2] }}</td>
            <td class="text-center">{{ $row[3] }}</td>
            <td>{{ $row[4] }}</td>
            <td>
                <span class="font-bold" style="color: #b91c1c;">{{ $row[5] }}</span>
            </td>
            <td>{{ $row[6] }}</td>
            <td><code>{{ $row[7] }}</code></td>
            <td>{{ $row[8] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                No vulnerable evacuees registered matching the active filters.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
