<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Models\User;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return User::class;
    }

    public function getUserByUuid(string $uuid): Model
    {
        return $this->getBuilder()->where('uuid', $uuid)->firstOrFail();
    }
}
