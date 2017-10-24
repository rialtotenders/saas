<?php

namespace Perevorot\Users\Controllers;

use Flash;
use BackendMenu;
use RainLab\User\Controllers\UserGroups as BaseUserGroups;

/**
 * User Groups Back-end Controller
 */
class UserGroups extends BaseUserGroups
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Perevorot.Users', 'user', 'groups');
    }
}
