<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * A draft pool such as "Apex Predators" or "Bugs".
 *
 * Rules are a list of {field, operator, value} conditions matched against animal columns, e.g.
 * [{"field": "taxon_class", "operator": "=", "value": "Insecta"}]. All rules must match.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property list<array{field: string, operator: string, value: mixed}>|null $rules
 * @property bool $size_relative
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Collection<int, Animal> $animals
 *
 * @author Richard Patmore <72patmore.r@gmail.com>
 */
#[Fillable(['slug', 'name', 'description', 'icon', 'rules', 'size_relative', 'is_active', 'sort_order'])]
class Category extends Model
{
	/** @use HasFactory<CategoryFactory> */
	use HasFactory;

	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'rules' => 'array',
			'size_relative' => 'boolean',
			'is_active' => 'boolean',
		];
	}

	public function getRouteKeyName(): string
	{
		return 'slug';
	}

	/**
	 * @return BelongsToMany<Animal, $this>
	 */
	public function animals(): BelongsToMany
	{
		return $this->belongsToMany(Animal::class)->withPivot('is_manual');
	}
}
