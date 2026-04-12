{{-- Cake `Elements/smslogs/chatwindow.ctp` --}}
<div class="modal-dialog" style="margin: 0;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h6 class="modal-title">
                <span class="status-mark bg-success position-left"></span>
                @if(!empty($renter))
                    {{ e(trim(($renter->first_name ?? '').' '.($renter->last_name ?? ''))) }}
                @else
                    {{ e($phone ?? '') }}
                @endif
            </h6>
        </div>
        <div class="modal-body">
            <ul class="media-list chat-list content-group" style="max-height: 350px;">
                @foreach($CsTwilioLogs as $CsTwilioLog)
                    @if((int)($CsTwilioLog->type ?? 0) === 2)
                        <li class="media">
                            <div class="media-left">
                                <a href="#">
                                    @if(!empty(optional($renter)->photo))
                                        <img src="{{ rtrim(legacy_site_url(), '/') }}/Images/index?width=150&amp;height=150&amp;image=/img/user_pic/{{ rawurlencode((string)$renter->photo) }}"
                                             class="img-circle" alt="">
                                    @else
                                        <img src="{{ legacy_asset('img/user_pic/no_image.gif') }}" class="img-circle" alt="">
                                    @endif
                                </a>
                            </div>
                            <div class="media-body">
                                <div class="media-content">{!! nl2br(e($CsTwilioLog->msg ?? '')) !!}</div>
                                <span class="media-annotation display-block mt-10">
                                    @if(!empty($CsTwilioLog->created))
                                        {{ \Carbon\Carbon::parse($CsTwilioLog->created)->format('d M, Y h:i A') }}
                                    @endif
                                    <a href="#"><i class="icon-pin-alt position-right text-muted"></i></a>
                                </span>
                            </div>
                        </li>
                    @else
                        <li class="media reversed">
                            <div class="media-body">
                                <div class="media-content">{!! nl2br(e($CsTwilioLog->msg ?? '')) !!}</div>
                                <span class="media-annotation display-block mt-10">
                                    @if(!empty($CsTwilioLog->created))
                                        {{ \Carbon\Carbon::parse($CsTwilioLog->created)->format('d M, Y h:i A') }}
                                    @endif
                                    <a href="#"><i class="icon-pin-alt position-right text-muted"></i></a>
                                </span>
                            </div>
                            <div class="media-right">
                                <a href="#"><span class="img-circle display-block bg-blue p-5">Me</span></a>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
            <textarea name="enter-message" id="usermessage" class="form-control content-group" rows="3" cols="1" placeholder="Enter your message..."></textarea>
            <div class="row">
                <div class="col-xs-6">
                    <ul class="icons-list icons-list-extended mt-10"></ul>
                </div>
                <div class="col-xs-6 text-right">
                    <button type="button"
                            onclick="sendmessage('{{ e($phone ?? '') }}','{{ (int)($renter->id ?? 0) }}')"
                            class="btn bg-teal-400 btn-labeled btn-labeled-right">
                        <b><i class="icon-circle-right2"></i></b> Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
