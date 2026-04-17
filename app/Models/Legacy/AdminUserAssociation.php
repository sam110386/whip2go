<?php

namespace App\Models\Legacy;

class AdminUserAssociation extends LegacyModel
{
    protected $table = 'admin_user_associations';

    protected $fillable = [
        'user_id',
        'admin_id',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    /**
     * function to associate new Registrated User with Lead
     */
    public static function saveLeadAssociation($phone, $userId)
    {
        // Simple implementation of the association logic
        $lead = CsLead::where('phone', $phone)
            ->where('type', 2)
            ->whereIn('status', [0, 2])
            ->first();

        if ($lead) {
            try {
                $lead->update(['status' => 1, 'modified' => now()->toDateTimeString()]);

                // Save Association
                self::create([
                    'user_id' => $userId,
                    'admin_id' => $lead->admin_id,
                    'created' => now()->toDateTimeString(),
                    'modified' => now()->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                // Log or handle error if needed
            }
        }
    }
}
