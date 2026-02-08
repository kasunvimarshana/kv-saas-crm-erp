<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Translatable Trait
 * 
 * Provides multi-language support for model attributes following the pattern
 * from polymorphic translatable models analysis.
 */
trait Translatable
{
    /**
     * The attributes that should be translatable.
     *
     * @var array
     */
    protected $translatable = [];

    /**
     * Boot the translatable trait for a model.
     *
     * @return void
     */
    public static function bootTranslatable()
    {
        // Register event listeners for model events
    }

    /**
     * Get a translated attribute.
     *
     * @param string $key
     * @param string|null $locale
     * @return mixed
     */
    public function translate(string $key, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        return $this->translations()
            ->where('locale', $locale)
            ->where('attribute', $key)
            ->value('value') ?? $this->getAttribute($key);
    }

    /**
     * Get all translations for the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations()
    {
        return $this->morphMany('App\Models\Translation', 'translatable');
    }

    /**
     * Set a translated attribute.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $locale
     * @return void
     */
    public function setTranslation(string $key, $value, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        $this->translations()->updateOrCreate(
            [
                'locale' => $locale,
                'attribute' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    /**
     * Check if an attribute is translatable.
     *
     * @param string $key
     * @return bool
     */
    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->translatable);
    }
}
