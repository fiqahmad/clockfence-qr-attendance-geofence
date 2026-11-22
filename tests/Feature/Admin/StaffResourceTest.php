<?php

use App\Enums\Role;
use App\Enums\StaffStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Site;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->withRole(Role::Admin)->create();
});

it('invites a new staff member by email', function () {
    Notification::fake();
    $site = Site::factory()->create();
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Nur Aisyah',
            'email' => 'aisyah@example.com',
            'staff_no' => 'S0042',
            'site_id' => $site->id,
            'role' => Role::Staff->value,
            'locale' => 'ms',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $staffMember = User::where('email', 'aisyah@example.com')->sole();

    expect($staffMember->status)->toBe(StaffStatus::Invited)
        ->and($staffMember->primaryRole())->toBe(Role::Staff)
        ->and($staffMember->site->is($site))->toBeTrue()
        ->and($staffMember->locale)->toBe('ms');

    Notification::assertSentTo($staffMember, StaffInvitation::class);
});

it('does not offer archived sites as a home site', function () {
    $archivedSite = Site::factory()->archived()->create();
    $this->actingAs($this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'x@example.com', 'site_id' => $archivedSite->id, 'role' => 'staff', 'locale' => 'en'])
        ->call('create')
        ->assertHasFormErrors(['site_id']);
});

it('changes a role on edit', function () {
    $staffMember = User::factory()->withRole(Role::Staff)->create();
    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $staffMember->getRouteKey()])
        ->assertSchemaStateSet(['role' => Role::Staff->value])
        ->fillForm(['role' => Role::Manager->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($staffMember->fresh()->primaryRole())->toBe(Role::Manager);
});

it('deactivates and reactivates but never deletes', function () {
    $staffMember = User::factory()->withRole(Role::Staff)->create();
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)->callTableAction('deactivate', $staffMember);
    expect($staffMember->fresh()->status)->toBe(StaffStatus::Inactive);

    Livewire::test(ListUsers::class)->callTableAction('reactivate', $staffMember);
    expect($staffMember->fresh()->status)->toBe(StaffStatus::Active)
        ->and($this->admin->can('delete', $staffMember))->toBeFalse();
});

it('does not let an admin deactivate themselves', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)->assertTableActionHidden('deactivate', $this->admin);
});

it('reactivates someone who never accepted the invitation as invited', function () {
    $staffMember = User::factory()->invited()->create(['email_verified_at' => null]);
    $staffMember->deactivate();

    $staffMember->reactivate();

    expect($staffMember->fresh()->status)->toBe(StaffStatus::Invited);
});

it('keeps managers and staff out of staff management', function (Role $role) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(ListUsers::getUrl())->assertForbidden();
})->with([Role::Manager, Role::Staff]);

it('keeps inactive admins out of the panel', function () {
    $this->admin->deactivate();

    $this->actingAs($this->admin)->get('/admin')->assertForbidden();
});

it('ends every session and remember-me login when a staff member is deactivated', function () {
    $staffMember = User::factory()->withRole(Role::Staff)->create(['remember_token' => 'old-token']);
    DB::table('sessions')->insert([
        'id' => 'session-of-staff-member',
        'user_id' => $staffMember->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $staffMember->deactivate();

    expect(DB::table('sessions')->where('user_id', $staffMember->id)->exists())->toBeFalse()
        ->and($staffMember->fresh()->remember_token)->not->toBe('old-token');
});
