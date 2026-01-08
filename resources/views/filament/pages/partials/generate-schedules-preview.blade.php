<div class="space-y-4">
    <div class="text-sm text-gray-600">You are about to generate schedules for <strong>{{ $count }}</strong> course(s) across the following town(s): <strong>{{ implode(', ', $towns) }}</strong>.</div>
    @if (isset($period_info))
        <div class="text-sm text-blue-600 bg-blue-50 p-2 rounded">{{ $period_info }}</div>
    @endif
    <div class="max-h-64 overflow-auto border rounded p-2 bg-gray-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left">
                    <th class="py-1 pr-2">Course</th>
                    <th class="py-1 pl-2">Range</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t">
                        <td class="py-1 pr-2">{{ $row['title'] }}</td>
                        <td class="py-1 pl-2">{{ $row['range'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($more > 0)
        <div class="text-xs text-gray-500">and {{ $more }} more…</div>
    @endif
    <div class="text-xs text-gray-500">Note: duplicates will be skipped if the option is enabled; overwrite mode will delete existing schedules for the selected towns first.</div>
</div>

