<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Database\Eloquent\Model;

interface RefreshTokenRepositoryInterface extends RepositoryInterface
{
    /**
     * Get refresh token using user ID
     *
     * @param string $userId
     * @return Model
     */
    public function getUsingUserId(string $userId): Model;
}
