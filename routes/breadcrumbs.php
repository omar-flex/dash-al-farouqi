<?php

use App\Models\User;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Spatie\Permission\Models\Role;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('dashboard'));
});

// Home > Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Dashboard', route('dashboard'));
});

Breadcrumbs::for('contract.hotels.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Hotels', route('contract.hotels'));
});

Breadcrumbs::for('contract.hotels.show', function (BreadcrumbTrail $trail, $hotel) {
    $trail->parent('contract.hotels.index');
    $trail->push(ucwords($hotel->partner_id->name), route('contract.hotels.show', $hotel->partner_id->id));
});

Breadcrumbs::for('contract.transportations.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Transportation', route('contract.transportations'));
});

Breadcrumbs::for('contract.transportations.show', function (BreadcrumbTrail $trail, $transportation) {
    $trail->parent('contract.transportations.index');
    $trail->push(ucwords($transportation?->partner_id?->name), route('contract.transportations.show', $transportation->partner_id->id));
});

Breadcrumbs::for('contract.restaurants.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Restaurant', route('contract.restaurants'));
});

Breadcrumbs::for('contract.restaurants.show', function (BreadcrumbTrail $trail, $restaurant) {
    $trail->parent('contract.restaurants.index');
    $trail->push(ucwords($restaurant?->name), route('contract.restaurants.show', $restaurant->id));
});

Breadcrumbs::for('contract.guides.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Guides', route('contract.guides'));
});

Breadcrumbs::for('contract.guides.show', function (BreadcrumbTrail $trail, $guide) {
    $trail->parent('contract.guides.index');
    $trail->push(ucwords($guide?->name), route('contract.guides.show', $guide->id));
});

/*Breadcrumbs::for('contract.transportations.show', function (BreadcrumbTrail $trail, $transportation) {
    $trail->parent('contract.hotels.index');
    $trail->push(ucwords($transportation?->partner_id?->name), route('contract.transportations.show', $transportation->partner_id->id));
});*/

/*// Home > Dashboard > User Management
Breadcrumbs::for('user-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('User Management', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users
Breadcrumbs::for('user-management.users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Users', route('user-management.users.index'));
});

// Home > Dashboard > User Management > Users > [User]
Breadcrumbs::for('user-management.users.show', function (BreadcrumbTrail $trail, User $user) {
    $trail->parent('user-management.users.index');
    $trail->push(ucwords($user->name), route('user-management.users.show', $user));
});

// Home > Dashboard > User Management > Roles
Breadcrumbs::for('user-management.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Roles', route('user-management.roles.index'));
});

// Home > Dashboard > User Management > Roles > [Role]
Breadcrumbs::for('user-management.roles.show', function (BreadcrumbTrail $trail, Role $role) {
    $trail->parent('user-management.roles.index');
    $trail->push(ucwords($role->name), route('user-management.roles.show', $role));
});

// Home > Dashboard > User Management > Permission
Breadcrumbs::for('user-management.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('user-management.index');
    $trail->push('Permissions', route('user-management.permissions.index'));
});*/
