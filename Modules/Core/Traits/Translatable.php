<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Spatie\Translatable\HasTranslations as SpatieHasTranslations;

/**
 * Translatable Trait
 *
 * Provides multi-language support for model attributes using Spatie's
 * laravel-translatable package. This follows the analysis from polymorphic
 * translatable models and uses a proven, stable LTS package.
 *
 * Usage:
 * 1. Add trait to your model: use Translatable;
 * 2. Define translatable attributes: public $translatable = ['name', 'description'];
 * 3. Use setTranslation/getTranslation methods or access attributes directly
 *
 * Example:
 * $product->setTranslation('name', 'en', 'Product Name');
 * $product->setTranslation('name', 'es', 'Nombre del Producto');
 * $name = $product->getTranslation('name', 'es');
 *
 * @link https://github.com/spatie/laravel-translatable
 */
trait Translatable
{
    use SpatieHasTranslations;

    /**
     * Get the list of translatable attributes.
     * This is used by Spatie's package.
     *
     * @return array<string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }
}
