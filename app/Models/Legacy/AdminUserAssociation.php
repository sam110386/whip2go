<?php

namespace App\Models\Legacy;

class AdminUserAssociation extends LegacyModel
{
    protected $table = 'admin_user_associations';

    /**
     * function to associate new Registrated User with Lead
     */
    public static function saveLeadAssociation($phone, $userId)
    {
        // Simple implementation of the association logic
        $lead = \DB::table('cs_leads')
            ->where('phone', $phone)
            ->where('type', 2)
            ->whereIn('status', [0, 2])
            ->first();

        if ($lead) {
            try {
                \DB::table('cs_leads')
                    ->where('id', $lead->id)
                    ->update(['status' => 1]);

                // Save Association
                self::create([
                    'user_id' => $userId,
                    'admin_id' => $lead->admin_id
                ]);
            } catch (\Exception $e) {
                // Log or handle error if needed
            }
        }
    }
}
