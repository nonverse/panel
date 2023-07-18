<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Database\Eloquent\Model;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Get a user by their UUID
     *
     * @param string $uuid
     * @return Model
     */
    public function getUserByUuid(string $uuid): Model;
}
