<?php

namespace App\Tables;

use App\Http\Resources\Admin\RoleResource;
use App\Models\Role;
use Dcodegroup\LaravelDsgTable\Actions\CrudActions;
use Dcodegroup\LaravelDsgTable\Actions\RowActions;
use Dcodegroup\LaravelDsgTable\Columns\ActionsColumn;
use Dcodegroup\LaravelDsgTable\Columns\Column;
use Dcodegroup\LaravelDsgTable\Contracts\TableInterface;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RolesTable implements TableInterface
{
    public function authorisation(mixed $arguments = null): Response|bool
    {
        return Gate::authorize('viewAny', Role::class);
    }

    public function resourceCollection(mixed $param = null): AnonymousResourceCollection
    {
        $roles = QueryBuilder::for(Role::query()->withCount('users'))
            ->allowedFilters(
                AllowedFilter::callback('term', function ($query, $value): void {
                    $query->where('name', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts('name', 'users_count', 'updated_at')
            ->defaultSort('name')
            ->paginate(request('per_page', 15));

        return RoleResource::collection($roles);
    }

    public function fields(): Collection
    {
        return collect([
            Column::make('name', 'Name')->isSortable()->toArray(),
            Column::make('users_count', 'Users')->isSortable()->toArray(),
            Column::make('updated_at', 'Last updated')->isSortable()->toArray(),
            ActionsColumn::make(width: '100px')->toArray(),
        ]);
    }

    public function filters(Request $request, mixed $param = null): array
    {
        return [];
    }

    public function actionsFor(mixed $model, mixed $param = null): array
    {
        return CrudActions::for($model, 'admin.roles')
            ->when(Gate::allows('update', $model), fn (RowActions $actions) => $actions->edit('admin.roles.edit'))
            ->when(Gate::allows('delete', $model), fn (RowActions $actions) => $actions->delete('admin.roles.destroy'))
            ->toArray();
    }
}
