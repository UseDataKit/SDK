<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsFormRepository;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsSchemaProvider;
use DataKit\DataViews\Query\ColumnType;
use PHPUnit\Framework\TestCase;

/**
 * Field discovery for Gravity Forms.
 */
final class GravityFormsSchemaProviderTest extends TestCase
{
    private GravityFormsSchemaProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new GravityFormsSchemaProvider($this->forms());
    }

    public function test_system_fields_are_present_without_a_form_scope(): void
    {
        $schema = $this->provider->describe([]);

        self::assertTrue($schema->hasField('entry_id'));
        self::assertTrue($schema->hasField('payment_amount'));
        self::assertTrue($schema->hasField('form_title'));
        self::assertTrue($schema->hasField('created_at'), 'created_at is the cross-source alias for date_created.');
        self::assertFalse($schema->hasField('field:3'));
    }

    public function test_form_fields_are_keyed_with_the_field_prefix(): void
    {
        $schema = $this->provider->describe(['form_ids' => [42]]);

        self::assertTrue($schema->hasField('field:3'), 'A per-form field must be queryable.');
        self::assertFalse($schema->hasField('3'), 'A bare numeric key is exactly what PHP casts to int.');
        self::assertSame('Headcount', $schema->getField('field:3')->label);
    }

    public function test_every_field_key_stays_a_string_as_an_array_key(): void
    {
        $schema = $this->provider->describe(['form_ids' => [42]]);

        // array_flip() reproduces what a column map does to these keys: a
        // numeric-string key comes back as an int and every string helper
        // downstream throws a TypeError on it.
        foreach (array_keys(array_flip($schema->fieldNames())) as $key) {
            self::assertIsString($key);
        }
    }

    public function test_multi_input_fields_expand_to_prefixed_sub_inputs(): void
    {
        $schema = $this->provider->describe(['form_ids' => [42]]);

        self::assertTrue($schema->hasField('field:1.3'), 'Name fields store each input under its own meta key.');
        self::assertSame('Your name (First)', $schema->getField('field:1.3')->label);
    }

    public function test_numeric_fields_are_typed_and_aggregatable(): void
    {
        $schema = $this->provider->describe(['form_ids' => [42]]);

        self::assertSame(ColumnType::Float, $schema->getField('field:3')->type);
        self::assertTrue($schema->getField('field:3')->aggregatable);
        self::assertFalse($schema->getField('field:5')->aggregatable);
    }

    public function test_choice_fields_advertise_their_values(): void
    {
        $schema = $this->provider->describe(['form_ids' => [42]]);

        self::assertSame(['red' => 'Red', 'blue' => 'Blue'], $schema->getField('field:5')->enumValues);
    }

    public function test_an_unreadable_form_contributes_nothing(): void
    {
        $schema = $this->provider->describe(['form_ids' => [999]]);

        self::assertTrue($schema->hasField('entry_id'));
        self::assertFalse($schema->hasField('field:3'));
    }

    public function test_a_single_form_id_scope_is_accepted(): void
    {
        $schema = $this->provider->describe(['form_id' => 42]);

        self::assertTrue($schema->hasField('field:3'));
    }

    /**
     * A one-form repository: a name field, a number field and a select.
     *
     * The `fields` entries are objects because that is what `GFAPI::get_form()`
     * returns (GF_Field instances), and `inputs` stays an array on those.
     */
    private function forms(): GravityFormsFormRepository
    {
        return new class implements GravityFormsFormRepository {
            public function getForm(int $formId): ?array
            {
                if ($formId !== 42) {
                    return null;
                }

                return [
                    'id' => 42,
                    'title' => 'Signups',
                    'fields' => [
                        (object) [
                            'id' => 1,
                            'type' => 'name',
                            'label' => 'Your name',
                            'inputs' => [
                                ['id' => '1.3', 'label' => 'First'],
                                ['id' => '1.6', 'label' => 'Last'],
                            ],
                        ],
                        (object) ['id' => 3, 'type' => 'number', 'label' => 'Headcount'],
                        (object) [
                            'id' => 5,
                            'type' => 'select',
                            'label' => 'Colour',
                            'choices' => [
                                ['value' => 'red', 'text' => 'Red'],
                                ['value' => 'blue', 'text' => 'Blue'],
                            ],
                        ],
                    ],
                ];
            }
        };
    }
}
