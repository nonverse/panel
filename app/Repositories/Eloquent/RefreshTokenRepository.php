<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Contracts\Repository\RefreshTokenRepositoryInterface;
use Pterodactyl\Models\RefreshToken;

class RefreshTokenRepository extends EloquentRepository implements RefreshTokenRepositoryInterface
{

    public function model(): string
    {
        return RefreshToken::class;
    }

    public function getUsingUserId(string $userId): Model
    {
        return $this->getBuilder()->where('user_id', $userId)->firstOrFail();
    }
}
