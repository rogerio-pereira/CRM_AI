<?php

namespace App\Livewire\Services;

use App\Enums\CommercialServiceCategory;
use App\Models\CommercialService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Services')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $activeFilter = 'all';

    public bool $showFormModal = false;

    public ?int $editingServiceId = null;

    public string $name = '';

    public string $description = '';

    public string $default_unit_price = '';

    public string $category_slug = CommercialServiceCategory::WebsiteDesignAndDevelopment->value;

    public bool $is_active = true;

    public function getServicesProperty(): LengthAwarePaginator
    {
        $query = CommercialService::orderBy('name');

        if ($this->search !== '') {
            $searchTerm = '%'.$this->search.'%';

            $query->where(function (Builder $searchQuery) use ($searchTerm) {
                $searchQuery->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category_slug', $this->categoryFilter);
        }

        if ($this->activeFilter === 'active') {
            $query->where('is_active', true);
        }

        if ($this->activeFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $services = $query->paginate(15);

        return $services;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingServiceId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $serviceId): void
    {
        $service = CommercialService::findOrFail($serviceId);

        $this->editingServiceId = $service->id;
        $this->name = $service->name;
        $this->description = $service->description ?? '';
        $this->default_unit_price = $service->default_unit_price;
        $this->category_slug = $service->category_slug->value;
        $this->is_active = $service->is_active;
        $this->showFormModal = true;
        $this->resetValidation();
    }

    public function saveService(): void
    {
        $rules = self::formRules();
        $validated = $this->validate($rules);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'default_unit_price' => $validated['default_unit_price'],
            'category_slug' => $validated['category_slug'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingServiceId === null) {
            CommercialService::create($attributes);
            Flux::toast(variant: 'success', text: __('Service created.'));
        } else {
            $service = CommercialService::findOrFail($this->editingServiceId);
            $service->update($attributes);
            Flux::toast(variant: 'success', text: __('Service updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $serviceId): void
    {
        $service = CommercialService::findOrFail($serviceId);
        $newActiveStatus = ! $service->is_active;

        $service->update([
            'is_active' => $newActiveStatus,
        ]);

        Flux::toast(variant: 'success', text: __('Service status updated.'));
    }

    public function render(): View
    {
        return view('livewire.services.index');
    }

    /**
     * @return array<string, mixed>
     */
    private static function formRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'category_slug' => ['required', Rule::enum(CommercialServiceCategory::class)],
            'is_active' => ['boolean'],
        ];
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->default_unit_price = '';
        $this->category_slug = CommercialServiceCategory::WebsiteDesignAndDevelopment->value;
        $this->is_active = true;
        $this->resetValidation();
    }
}
