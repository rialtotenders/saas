<?php namespace Perevorot\Users\Models;

use DB;

/**
 * Model
 */
class UsersExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $artworks = User::all();

        $artworks->each(function($artwork) use ($columns) {
            $artwork->money = $artwork->money();
            $artwork->user_groups = implode(", ", $artwork->groupCodes());
            $artwork->addVisible($columns);
        });
        return $artworks->toArray();
    }
}