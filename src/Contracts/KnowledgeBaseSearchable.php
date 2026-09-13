<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface KnowledgeBaseSearchable
{
    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, Model>
     */
    public function search(string $query, array $options = []): Collection;
}
