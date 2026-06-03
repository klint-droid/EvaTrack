@extends('exports.layout')

@section('content')
<table class="data-table">
    <thead>
        <tr>
            @foreach($headers as $header)
            <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            @foreach($row as $cell)
            <td>{{ $cell }}</td>
            @endforeach
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($headers) }}" class="text-center" style="padding: 20px; color: #94a3b8;">
                No records found matching the active report filter criteria.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
