<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        $organizer = Role::create(['name' => 'organizer']);
        $client = Role::create(['name' => 'client']);

        $permissions = collect([
            'manage-users',
            'manage-events',
            'sell-tickets',
            'validate-tickets',
            'view-reports',
        ])->map(fn ($name) => Permission::create(['name' => $name]));

        $admin->permissions()->attach($permissions->pluck('id'));
        $organizer->permissions()->attach(
            $permissions->whereIn('name', ['manage-events', 'validate-tickets', 'view-reports'])->pluck('id')
        );
        $client->permissions()->attach(
            $permissions->whereIn('name', ['sell-tickets'])->pluck('id')
        );

        $adminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@ticketera.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $adminUser->roles()->attach($admin);

        $organizerUser = User::create([
            'name' => 'Organizador Demo',
            'email' => 'organizer@ticketera.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $organizerUser->roles()->attach($organizer);

        $clientUser = User::create([
            'name' => 'Cliente Demo',
            'email' => 'client@ticketera.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $clientUser->roles()->attach($client);

        $event1 = Event::create([
            'user_id' => $organizerUser->id,
            'title' => 'Concierto Rock Nacional',
            'description' => 'Gran concierto de rock con bandas nacionales.',
            'location' => 'Estadio Nacional',
            'date' => now()->addWeeks(2),
            'is_active' => true,
        ]);

        TicketType::insert([
            ['event_id' => $event1->id, 'name' => 'General', 'price' => 25.00, 'quantity' => 500, 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $event1->id, 'name' => 'VIP', 'price' => 75.00, 'quantity' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $event1->id, 'name' => 'Premium', 'price' => 150.00, 'quantity' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $event2 = Event::create([
            'user_id' => $organizerUser->id,
            'title' => 'Festival de Comida',
            'description' => 'Festival gastronómico con food trucks y música en vivo.',
            'location' => 'Parque Central',
            'date' => now()->addMonth(),
            'is_active' => true,
        ]);

        TicketType::insert([
            ['event_id' => $event2->id, 'name' => 'General', 'price' => 10.00, 'quantity' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $event2->id, 'name' => 'VIP', 'price' => 35.00, 'quantity' => 200, 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $event2->id, 'name' => 'Premium', 'price' => 60.00, 'quantity' => 50, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}