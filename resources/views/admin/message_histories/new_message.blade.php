<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">New message</h3>
    <input type="hidden" id="msg_owner_id" value="{{ $ownerId }}">
    <input type="hidden" id="msg_renter_id" value="{{ $renterId }}">
    <textarea id="msg_text" rows="4" style="width:100%;" placeholder="Type message..."></textarea>
    <div style="margin-top:8px;">
        <button type="button" onclick="sendLinkedMessage()">Send</button>
    </div>
</div>
<script>
    function sendLinkedMessage() {
        var sender = document.getElementById('msg_owner_id').value;
        var receiver = document.getElementById('msg_renter_id').value;
        var message = document.getElementById('msg_text').value;
        var url = location.pathname.indexOf('/cloud/') >= 0 ? '/cloud/message_histories/sendnewmessage' : '/admin/message_histories/sendnewmessage';
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({sender_id: sender, receiver_id: receiver, message: message})
        }).then(r => r.json()).then(function (res) {
            alert(res.message || 'Done');
        });
    }
</script>

