@php
    $columns ??= [];
@endphp

@foreach ($columns as $column)
    @php
        $field = $column['field'] ?? '';
        $title = $column['title'] ?? '';
        $style = $column['style'] ?? '';
        $sortable = $column['sortable'] ?? true;

        if ($sortable) {
            $currentSort = request('sort');
            $currentDirection = strtolower(request('direction', 'desc'));

            $isSorted = $currentSort == $field;
            $nextDirection = $isSorted && $currentDirection == 'asc' ? 'desc' : 'asc';

            $params = request()->all();
            $params['sort'] = $field;
            $params['direction'] = $nextDirection;

            $url = url()->current() . '?' . http_build_query($params);

            $icon = '';
            if ($isSorted) {
                $icon = $currentDirection == 'asc' ? ' <i class="icon-arrow-up8"></i>' : ' <i class="icon-arrow-down8"></i>';
            }
        }
    @endphp

    @php
        $isHtml = $column['html'] ?? false;
    @endphp

    <th valign="top" style="{{ $style }}">
        @if ($sortable)
            <a href="{{ $url }}" class="sort-link {{ $isSorted ? 'active-sort' : '' }}">
                @if($isHtml) {!! $title !!} @else {{ $title }} @endif {!! $icon !!}
            </a>
        @else
            @if($isHtml) {!! $title !!} @else {{ $title }} @endif
        @endif
    </th>
@endforeach