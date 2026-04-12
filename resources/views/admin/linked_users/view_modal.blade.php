<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Linked user</h3>
    <div><strong>Name:</strong> {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}</div>
    <div><strong>Email:</strong> {{ $user->email ?? '' }}</div>
    <div><strong>Phone:</strong> {{ $user->contact_number ?? '' }}</div>
    <div><strong>Status:</strong> {{ (int)($user->status ?? 0) }}</div>
    <div><strong>Linked admin:</strong> {{ $assoc->admin_id ?? '' }}</div>
</div>

