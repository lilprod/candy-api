<?php

namespace GetCandy\Api\Core\Customers\Actions;

use GetCandy\Api\Core\Scaffold\AbstractAction;
use GetCandy\Api\Core\Collections\Models\Collection;
use GetCandy\Api\Core\Customers\Models\CustomerGroup;
use GetCandy\Api\Core\Collections\Resources\CollectionCollection;

class FetchCustomerGroups extends AbstractAction
{
    /**
     * Determine if the user is authorized to make this action.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the action.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'per_page' => 'numeric|max:200',
            'paginate' => 'boolean',
        ];
    }

    public function afterValidator($validator)
    {
        $this->set('paginate', $this->paginate === null ?: $this->paginate);
    }

    /**
     * Execute the action and return a result.
     *
     * @return mixed
     */
    public function handle()
    {
        $includes = $this->resolveEagerRelations();

        if (! $this->paginate) {
            return CustomerGroup::with($includes)->get();
        }

        return CustomerGroup::with($includes)->paginate($this->per_page ?? 50);
    }

    /**
     * Returns the response from the action.
     *
     * @param   \GetCandy\Api\Core\Customers\Models\CustomerGroup|Illuminate\Pagination\LengthAwarePaginator  $result
     * @param   \Illuminate\Http\Request  $request
     *
     * @return  CustomerGroupCollection
     */
    public function response($result, $request): CustomerGroupCollection
    {
        return new CustomerGroupCollection($result);
    }
}
