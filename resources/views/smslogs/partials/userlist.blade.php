{{-- Cake `Elements/smslogs/userlist.ctp` (Paginator AJAX wiring omitted; links use full GET navigation). --}}
<div class="panel panel-flat">
    <div class="panel-heading"></div>
    <div class="panel-body">
        <ul class="media-list" id="userlist">
            @foreach($logsPaginator as $log)
                @php
                    $rid = (int)($log->renter_user_id ?? 0);
                @endphp
                <li class="media" onclick="loadchat('{{ e($log->renter_phone ?? '') }}','{{ $rid }}')">
                    <div class="media-left media-middle">
                        <a href="#">
                            @if(!empty($log->photo))
                                <img class="img-circle" style="max-height: 150px;max-width: 150px;"
                                     src="{{ rtrim(legacy_site_url(), '/') }}/Images/index?width=150&amp;height=150&amp;image=/img/user_pic/{{ rawurlencode((string)$log->photo) }}"
                                     alt="">
                            @else
                                <img class="img-circle" style="max-height: 150px;max-width: 150px;"
                                     src="{{ legacy_asset('img/user_pic/no_image.gif') }}" alt="">
                            @endif
                        </a>
                    </div>
                    <div class="media-body">
                        <div class="media-heading text-semibold">
                            @if(!empty($log->first_name) || !empty($log->last_name))
                                {{ e(trim(($log->first_name ?? '').' '.($log->last_name ?? ''))) }}
                            @else
                                {{ e($log->renter_phone ?? '') }}
                            @endif
                        </div>
                        <span class="text-muted">{{ e(\Illuminate\Support\Str::limit((string)($log->msg ?? ''), 50, '')) }}</span>
                    </div>
                    <div class="media-right media-middle">
                        <ul class="icons-list icons-list-extended text-nowrap">
                            <li><a href="#" data-popup="tooltip" title="Chat"><i class="icon-comment"></i></a></li>
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@if($logsPaginator->hasPages())
    <section class="pagging">
        <div class="pagination pagination-rounded pull-right" style="margin:10px 0;">
            {{ $logsPaginator->appends(['searchKey' => $keyword ?? ''])->links() }}
        </div>
    </section>
@endif
