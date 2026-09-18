<?php

namespace Tests\Feature;

use App\Models\Diskon;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DiskonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $ubahan
     * @return array<string, mixed>
     */
    private function form(array $ubahan = []): array
    {
        return array_merge([
            'no_hp' => '081234567890',
            'diskon' => 10_000,
            'keterangan' => 'Kakak beradik',
            'is_universal' => false,
        ], $ubahan);
    }

    public function test_guest_cannot_touch_any_discount_endpoint(): void
    {
        $diskon = Diskon::factory()->create();

        $this->post(route('admin.diskon.store'), $this->form())->assertRedirect(route('login'));
        $this->put(route('admin.diskon.update', $diskon->id), $this->form())->assertRedirect(route('login'));
        $this->delete(route('admin.diskon.destroy', $diskon->id))->assertRedirect(route('login'));

        $this->assertDatabaseCount('diskons', 1);
    }

    public function test_teacher_account_is_forbidden_from_managing_discounts(): void
    {
        $guru = User::factory()->guru()->create();
        $diskon = Diskon::factory()->create(['diskon' => 7_000]);

        $this->actingAs($guru)->postJson(route('admin.diskon.store'), $this->form())->assertForbidden();
        $this->actingAs($guru)->putJson(route('admin.diskon.update', $diskon->id), $this->form())->assertForbidden();
        $this->actingAs($guru)->deleteJson(route('admin.diskon.destroy', $diskon->id))->assertForbidden();

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'diskon' => 7_000]);
        $this->assertDatabaseCount('diskons', 1);
    }

    public function test_update_is_reachable_only_through_put_never_post(): void
    {
        $diskon = Diskon::factory()->create(['diskon' => 10_000]);

        $this->actingAs($this->admin())
            ->postJson('/admin/diskon/'.$diskon->id, $this->form(['diskon' => 99_000]))
            ->assertStatus(405);

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'diskon' => 10_000]);
    }

    public function test_delete_is_reachable_only_through_the_delete_verb(): void
    {
        $diskon = Diskon::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/admin/diskon/'.$diskon->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id]);
    }

    public function test_admin_creates_a_family_discount_and_the_phone_is_normalised(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form(['no_hp' => '081234567890']));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.no_hp', '+6281234567890')
            ->assertJsonPath('data.diskon', 10_000);

        $this->assertDatabaseHas('diskons', [
            'no_hp' => '+6281234567890',
            'diskon' => 10_000,
            'keterangan' => 'Kakak beradik',
        ]);
    }

    public function test_admin_creates_a_universal_discount_stored_without_a_phone(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form([
                'no_hp' => '081234567890',
                'is_universal' => true,
                'keterangan' => null,
            ]));

        $response->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('diskons', [
            'no_hp' => null,
            'diskon' => 10_000,
            'keterangan' => 'Diskon Massal',
        ]);
        $this->assertDatabaseCount('diskons', 1);
    }

    public function test_storing_a_universal_discount_twice_overwrites_instead_of_duplicating(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.diskon.store'), $this->form(['is_universal' => true, 'diskon' => 5_000]))
            ->assertOk();
        $this->actingAs($admin)
            ->postJson(route('admin.diskon.store'), $this->form(['is_universal' => true, 'diskon' => 8_000]))
            ->assertOk();

        $this->assertDatabaseCount('diskons', 1);
        $this->assertDatabaseHas('diskons', ['no_hp' => null, 'diskon' => 8_000]);
    }

    public function test_storing_the_same_phone_written_differently_overwrites_instead_of_duplicating(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.diskon.store'), $this->form(['no_hp' => '081234567890', 'diskon' => 5_000]))
            ->assertOk();
        $this->actingAs($admin)
            ->postJson(route('admin.diskon.store'), $this->form(['no_hp' => '+62 812-3456-7890', 'diskon' => 8_000]))
            ->assertOk();

        $this->assertDatabaseCount('diskons', 1);
        $this->assertDatabaseHas('diskons', ['no_hp' => '+6281234567890', 'diskon' => 8_000]);
    }

    public function test_a_family_discount_without_a_phone_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form(['no_hp' => '   ']));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Nomor HP wajib diisi untuk diskon spesifik.');

        $this->assertDatabaseCount('diskons', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function masukanTidakSah(): array
    {
        return [
            'nominal kosong' => [['diskon' => null], 'diskon'],
            'nominal negatif' => [['diskon' => -1], 'diskon'],
            'nominal bukan angka' => [['diskon' => 'sepuluh ribu'], 'diskon'],
            'is_universal hilang' => [['is_universal' => null], 'is_universal'],
            'keterangan kepanjangan' => [['keterangan' => 'a'], 'keterangan'],
        ];
    }

    /**
     * @param  array<string, mixed>  $ubahan
     */
    #[DataProvider('masukanTidakSah')]
    public function test_store_rejects_invalid_input(array $ubahan, string $field): void
    {
        if ($field === 'keterangan') {
            $ubahan['keterangan'] = str_repeat('a', 256);
        }

        $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form($ubahan))
            ->assertStatus(422)
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('diskons', 0);
    }

    /**
     * @param  array<string, mixed>  $ubahan
     */
    #[DataProvider('masukanTidakSah')]
    public function test_update_rejects_the_same_invalid_input_as_store(array $ubahan, string $field): void
    {
        if ($field === 'keterangan') {
            $ubahan['keterangan'] = str_repeat('a', 256);
        }

        $diskon = Diskon::factory()->create(['no_hp' => '+6281234567890', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form($ubahan))
            ->assertStatus(422)
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'diskon' => 9_000]);
    }

    public function test_a_discount_of_zero_is_allowed_because_it_means_no_cut(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form(['diskon' => 0]))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('diskons', ['no_hp' => '+6281234567890', 'diskon' => 0]);
    }

    public function test_an_unknown_id_is_rejected_by_validation_before_anything_is_written(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form(['id' => 9999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');

        $this->assertDatabaseCount('diskons', 0);
    }

    public function test_admin_updates_an_existing_discount_through_put(): void
    {
        $diskon = Diskon::factory()->create([
            'no_hp' => '+6281234567890',
            'diskon' => 10_000,
            'keterangan' => 'Lama',
        ]);

        $response = $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'no_hp' => '081234567890',
                'diskon' => 25_000,
                'keterangan' => 'Baru',
            ]));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Diskon berhasil diperbarui.')
            ->assertJsonPath('data.diskon', 25_000);

        $this->assertDatabaseHas('diskons', [
            'id' => $diskon->id,
            'no_hp' => '+6281234567890',
            'diskon' => 25_000,
            'keterangan' => 'Baru',
        ]);
        $this->assertDatabaseCount('diskons', 1);
    }

    public function test_update_can_turn_a_family_discount_into_a_universal_one(): void
    {
        $diskon = Diskon::factory()->create(['no_hp' => '+6281234567890']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'is_universal' => true,
                'keterangan' => null,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('diskons', [
            'id' => $diskon->id,
            'no_hp' => null,
            'keterangan' => 'Diskon Massal',
        ]);
    }

    public function test_update_can_turn_a_universal_discount_back_into_a_family_one(): void
    {
        $diskon = Diskon::factory()->massal()->create();

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'no_hp' => '081234567890',
                'is_universal' => false,
                'keterangan' => null,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('diskons', [
            'id' => $diskon->id,
            'no_hp' => '+6281234567890',
            'keterangan' => 'Potongan Diskon Keluarga',
        ]);
    }

    public function test_updating_a_discount_onto_its_own_phone_is_still_allowed(): void
    {
        $diskon = Diskon::factory()->create(['no_hp' => '+6281234567890', 'diskon' => 10_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'no_hp' => '081234567890',
                'diskon' => 30_000,
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'diskon' => 30_000]);
    }

    public function test_updating_a_universal_discount_onto_itself_is_still_allowed(): void
    {
        $diskon = Diskon::factory()->massal()->create(['diskon' => 3_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'is_universal' => true,
                'diskon' => 6_000,
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => null, 'diskon' => 6_000]);
    }

    public function test_update_refuses_to_point_a_discount_at_a_phone_that_already_has_one(): void
    {
        $lain = Diskon::factory()->create(['no_hp' => '+6281111111111', 'diskon' => 4_000]);
        $diskon = Diskon::factory()->create(['no_hp' => '+6282222222222', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form([
                'no_hp' => '081111111111',
                'diskon' => 50_000,
            ]))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => '+6282222222222', 'diskon' => 9_000]);
        $this->assertDatabaseHas('diskons', ['id' => $lain->id, 'diskon' => 4_000]);
    }

    public function test_the_duplicate_guard_survives_a_differently_written_phone_number(): void
    {
        Diskon::factory()->create(['no_hp' => '+6281111111111']);
        $diskon = Diskon::factory()->create(['no_hp' => '+6282222222222', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form(['no_hp' => '62 8111-111-1111']))
            ->assertStatus(422);

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => '+6282222222222', 'diskon' => 9_000]);
    }

    public function test_update_refuses_to_create_a_second_universal_discount(): void
    {
        Diskon::factory()->massal()->create(['diskon' => 3_000]);
        $diskon = Diskon::factory()->create(['no_hp' => '+6282222222222', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form(['is_universal' => true]))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => '+6282222222222']);
        $this->assertDatabaseCount('diskons', 2);
    }

    public function test_update_rejects_a_family_discount_left_without_a_phone(): void
    {
        $diskon = Diskon::factory()->create(['no_hp' => '+6281234567890', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', $diskon->id), $this->form(['no_hp' => '']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nomor HP wajib diisi untuk diskon spesifik.');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => '+6281234567890', 'diskon' => 9_000]);
    }

    public function test_updating_a_discount_that_no_longer_exists_reports_not_found(): void
    {
        $this->actingAs($this->admin())
            ->putJson(route('admin.diskon.update', 4321), $this->form())
            ->assertStatus(404)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Data diskon tidak ditemukan.');
    }

    public function test_store_carrying_an_id_goes_through_the_same_duplicate_guard_as_update(): void
    {
        Diskon::factory()->create(['no_hp' => '+6281111111111']);
        $diskon = Diskon::factory()->create(['no_hp' => '+6282222222222', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form([
                'id' => $diskon->id,
                'no_hp' => '081111111111',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'no_hp' => '+6282222222222', 'diskon' => 9_000]);
        $this->assertDatabaseCount('diskons', 2);
    }

    public function test_store_carrying_an_id_updates_that_row_instead_of_creating_one(): void
    {
        $diskon = Diskon::factory()->create(['no_hp' => '+6281234567890', 'diskon' => 9_000]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.diskon.store'), $this->form([
                'id' => $diskon->id,
                'no_hp' => '081234567890',
                'diskon' => 12_000,
            ]))
            ->assertOk()
            ->assertJsonPath('message', 'Diskon berhasil diperbarui.');

        $this->assertDatabaseCount('diskons', 1);
        $this->assertDatabaseHas('diskons', ['id' => $diskon->id, 'diskon' => 12_000]);
    }

    public function test_admin_deletes_a_discount(): void
    {
        $diskon = Diskon::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.diskon.destroy', $diskon->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('diskons', ['id' => $diskon->id]);
    }

    public function test_deleting_a_discount_twice_reports_not_found_the_second_time(): void
    {
        $diskon = Diskon::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->deleteJson(route('admin.diskon.destroy', $diskon->id))->assertOk();
        $this->actingAs($admin)->deleteJson(route('admin.diskon.destroy', $diskon->id))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Data diskon tidak ditemukan.');
    }

    public function test_deleting_one_family_discount_leaves_the_universal_one_alone(): void
    {
        $massal = Diskon::factory()->massal()->create();
        $keluarga = Diskon::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.diskon.destroy', $keluarga->id))
            ->assertOk();

        $this->assertDatabaseMissing('diskons', ['id' => $keluarga->id]);
        $this->assertDatabaseHas('diskons', ['id' => $massal->id, 'no_hp' => null]);
    }
}
