<?php

namespace App\Models;

use Filament\TeamChat\Concerns\HasTeamChat;

/**
 * AdminUser Model
 *
 * Extends the User model to provide team chat functionality exclusively for admins.
 * This model is used by the Filament team-chat package (configured in config/team-chat.php)
 * for all admin-to-admin chats and communications.
 *
 * Regular users use the User model with the Messageable trait for user-to-user chats.
 * This separation ensures:
 * - Admin chats are isolated in separate database tables (tc_* prefix)
 * - Only admin users (role='admin') can access team chat
 * - Regular users continue using the Musonza Chat system
 */
class AdminUser extends User
{
    use HasTeamChat;

    // Inherits all User properties and methods including touchOnline()
    // Only used when user is authenticated as admin in Filament panel
}

