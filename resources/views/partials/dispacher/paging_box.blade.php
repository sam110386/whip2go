@php
    $paginator ??= null;
    $limit ??= 50;
    $search = request('Search', []);
    if ($paginator && !($paginator instanceof \Illuminate\Contracts\Pagination\Paginator)) {
        $paginator = null;
    }
    if ($paginator) {
        $paginator->appends(request()->except('page'));
    }
@endphp

@if($paginator)
    <section class='pagging'>
        <div style="width: 30%; float: left;">
            <form name="frmRecordsPages" action="{{ url()->current() }}" method="post" style="display:inline;">
                @foreach(request()->except(['Record', 'page']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $k2 => $v2)
                                    <input type="hidden" name="{{ $key }}[{{ $k }}][{{ $k2 }}]" value="{{ $v2 }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                            @endif
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                {{'Show'}} &nbsp;
                <select name="Record[limit]" class="textbox pagingcls ajax-limit" onchange="this.form.submit()">
                    @foreach([25, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" {{ $limit == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                &nbsp;{{'Records per page'}} &nbsp;
            </form>
        </div>

        @if ($paginator->hasPages())
            <ul class="pagination pagination-rounded pull-right">

                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        <span class="page-link" aria-hidden="true">{{'Previous'}}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                            aria-label="@lang('pagination.previous')">{{'Previous'}}</a>
                    </li>
                @endif

                @php
                    $start = max($paginator->currentPage() - 3, 1);
                    $end = min($paginator->currentPage() + 3, $paginator->lastPage());
                @endphp

                @if($start > 1)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url(1) }}">{{'1'}}</a>
                    </li>
                    @if($start > 2)
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                    @endif
                @endif

                @for ($i = $start; $i <= $end; $i++)
                    @if ($i == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                    @endif
                @endfor

                @if($end < $paginator->lastPage())
                    @if($end < $paginator->lastPage() - 1)
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
                    @endif
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                            aria-label="@lang('pagination.next')">{{'Next'}}</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                        <span class="page-link" aria-hidden="true">{{'Next'}}</span>
                    </li>
                @endif
            </ul>
        @endif
    </section>
@endif