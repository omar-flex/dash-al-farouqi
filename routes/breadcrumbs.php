<?php

use App\Models\EnterRequest;
use App\Models\Outbound;
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

//Warehouse Management
Breadcrumbs::for('warehouse-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Warehouse Management', route('warehouse-management.warehouses.index'));
});

Breadcrumbs::for('warehouse-management.warehouses.index', function (BreadcrumbTrail $trail) {
    $trail->parent('warehouse-management.index');
    $trail->push('Warehouses', route('warehouse-management.warehouses.index'));
});

Breadcrumbs::for('warehouse-management.locations.index', function (BreadcrumbTrail $trail) {
    $trail->parent('warehouse-management.index');
    $trail->push('Locations', route('warehouse-management.locations.index'));
});

//Operation Management
Breadcrumbs::for('operation-management.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Operation Management', route('operation-management.enter_requests.index'));
});

Breadcrumbs::for('operation-management.enter_requests.index', function (BreadcrumbTrail $trail) {
    $trail->parent('operation-management.index');
    $trail->push('Inbound', route('operation-management.enter_requests.index'));
});


Breadcrumbs::for('operation-management.enter_requests.create', function (BreadcrumbTrail $trail) {
    $trail->parent('operation-management.enter_requests.index');
    $trail->push('Create', route('operation-management.enter_requests.create'));
});


Breadcrumbs::for('operation-management.enter_requests.show', function (BreadcrumbTrail $trail, EnterRequest $enterRequest) {
    $trail->parent('operation-management.enter_requests.index');
    $trail->push($enterRequest->bound_number, route('operation-management.enter_requests.show', $enterRequest));
});

//Operation Management
Breadcrumbs::for('manifest-authorizations.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Manifest Authorization', route('manifest-authorizations.inbounds.index'));
});

Breadcrumbs::for('manifest-authorizations.inbounds.index', function (BreadcrumbTrail $trail) {
    $trail->parent('manifest-authorizations.index');
    $trail->push('Inbounds', route('manifest-authorizations.inbounds.index'));
});

Breadcrumbs::for('operation-management.outbounds.index', function (BreadcrumbTrail $trail) {
    $trail->parent('operation-management.index');
    $trail->push('Outbounds', route('operation-management.outbounds.index'));
});

Breadcrumbs::for('operation-management.outbounds.show', function (BreadcrumbTrail $trail, Outbound $outbound) {
    $trail->parent('operation-management.outbounds.index');
    $trail->push($outbound->outbound_number, route('operation-management.outbounds.show', $outbound));
});

Breadcrumbs::for('products.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Products', route('products.index'));
});

Breadcrumbs::for('customers.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Customers', route('customers.index'));
});

Breadcrumbs::for('warehouse.report', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Warehouse Report', route('warehouses.report'));
});

// Home > Dashboard > User Management
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
});
