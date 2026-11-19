<?php

namespace App\Tables;

use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Dcodegroup\LaravelDsgTable\Actions\CrudActions;
use Dcodegroup\LaravelDsgTable\Actions\RowActions;
use Dcodegroup\LaravelDsgTable\Columns\ActionsColumn;
use Dcodegroup\LaravelDsgTable\Columns\Column;
use Dcodegroup\LaravelDsgTable\Columns\SlotColumn;
use Dcodegroup\LaravelDsgTable\Contracts\TableInterface;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UsersTable implements TableInterface
{
    public function authorisation(mixed $arguments = null): Response|bool
    {
        return Gate::authorize('viewAny', User::class);
    }

    public function resourceCollection(mixed $param = null): AnonymousResourceCollection
    {
        $users = QueryBuilder::for(User::query()->with('roles'))
            ->allowedFilters(
                AllowedFilter::callback('term', function ($query, $value): void {
                    $query->where(function ($query) use ($value): void {
                        $query->where('first_name', 'like', "%{$value}%")
                            ->orWhere('middle_name', 'like', "%{$value}%")
                            ->orWhere('last_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::exact('active'),
            )
            ->allowedSorts('first_name', 'last_name', 'email', 'active', 'updated_at')
            ->defaultSort('last_name')
            ->paginate(request('per_page', 15));

        return UserResource::collection($users);
    }

    public function fields(): Collection
    {
        return collect([
            SlotColumn::make('full_name', 'Name')->isSortable(false)->toArray(),
            Column::make('email', 'Email')->isSortable()->toArray(),
            SlotColumn::make('roles', 'Roles')->toArray(),
            SlotColumn::make('active', 'Status')->toArray(),
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
        return CrudActions::for($model, 'admin.users')
            ->when(Gate::allows('update', $model), fn (RowActions $actions) => $actions->edit('admin.users.edit'))
            ->when(Gate::allows('delete', $model), fn (RowActions $actions) => $actions->delete('admin.users.destroy'))
            ->toArray();
    }
}
