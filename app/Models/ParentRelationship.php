<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentRelationship extends Model
{
    protected $table = 'parent_relationships';

    protected $fillable = [
        'person_id',
        'parent_id',
        'gospel_contact_id',
        'parent_name',
        'relationship',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'parent_id');
    }

    public function gospelContact(): BelongsTo
    {
        return $this->belongsTo(
            GospelContact::class,
            'gospel_contact_id'
        );
    }

    protected static function booted(): void
    {
        static::saving(
            function (
                ParentRelationship $relationship
            ): void {
                /*
                 * An actual Person always takes precedence.
                 */
                if (filled($relationship->parent_id)) {
                    /*
                     * A canonical Person replaces any temporary
                     * Gospel Contact or legacy text identity.
                     */
                    $relationship->gospel_contact_id = null;
                    $relationship->parent_name = null;

                    return;
                }

                if (blank($relationship->gospel_contact_id)) {
                    return;
                }

                $contact = GospelContact::query()
                    ->find(
                        $relationship->gospel_contact_id
                    );

                if (! $contact) {
                    return;
                }

                /*
                 * If this Gospel Contact is already linked
                 * to People, store the canonical Person
                 * immediately instead.
                 */
                if (filled($contact->person_id)) {
                    $relationship->parent_id =
                        $contact->person_id;

                    $relationship->gospel_contact_id =
                        null;

                    $relationship->parent_name =
                        null;

                    return;
                }

                /*
                 * Keep a readable fallback name in case the
                 * Gospel Contact is deleted before promotion.
                 */
                $relationship->parent_name =
                    $contact->display_name;
            }
        );
    }
}
