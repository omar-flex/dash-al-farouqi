<!--begin::sidebar menu-->
<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <!--begin::Menu wrapper-->
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true"
         data-kt-scroll-activate="true" data-kt-scroll-height="auto"
         data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
         data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
        <!--begin::Menu-->
        <div class="menu menu-column menu-rounded menu-sub-indention px-3 fw-semibold fs-6" id="#kt_app_sidebar_menu"
             data-kt-menu="true" data-kt-menu-expand="false">
            <div class="menu-item {{ request()->routeIs('dashboard') ? 'here show' : '' }}">
                <a class="menu-link" href="{{ route('dashboard') }}">
                    <span class="menu-icon">
                        <i class="fa-sharp-duotone fa-solid fa-chart-network"></i>
                    </span>
                    <span class="menu-title">Dashboards</span>
                </a>
            </div>

            @canany(['list_warehouses','list_locations'])
                <div data-kt-menu-trigger="click"
                     class="menu-item menu-accordion {{ request()->routeIs('warehouse-management.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">
                        <i class="fa-sharp-duotone fa-solid fa-warehouse fa-lg"></i>
                    </span>
                    <span class="menu-title">WH Management</span>
                    <span class="menu-arrow"></span>
                </span>
                    <div class="menu-sub menu-sub-accordion">
                        @can('list_warehouses')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('warehouse-management.warehouses.*') ? 'active' : '' }}"
                                   href="{{ route('warehouse-management.warehouses.index') }}">
                             <span class="menu-icon">
                                     <i class="fa-sharp-duotone fa-solid fa-warehouse-full"></i>
                             </span>
                                    <span class="menu-title">Warehouses</span>
                                </a>
                            </div>
                        @endcanany
                        @can('list_locations')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('warehouse-management.locations.*') ? 'active' : '' }}"
                                   href="{{ route('warehouse-management.locations.index') }}">
                             <span class="menu-icon">
                                 <i class="fa-sharp-duotone fa-solid fa-chart-tree-map"></i>
                             </span>
                                    <span class="menu-title">Locations</span>
                                </a>
                            </div>
                        @endcanany
                    </div>

                </div>
            @endcan

            @hasrole('administrator')
            <div data-kt-menu-trigger="click"
                 class="menu-item menu-accordion {{ request()->routeIs('user-management.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">
                       <i class="fa-sharp-duotone fa-solid fa-users-gear fa-lg"></i>
                    </span>
                    <span class="menu-title">User Management</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.users.*') ? 'active' : '' }}"
                           href="{{ route('user-management.users.index') }}">
                             <span class="menu-icon">
                                      <i class="fa-sharp-duotone fa-solid fa-users"></i>
                             </span>
                            <span class="menu-title">Users</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}"
                           href="{{ route('user-management.roles.index') }}">
                            <span class="menu-icon">
                              <i class="fa-sharp-duotone fa-solid fa-shield-check"></i>
                             </span>
                            <span class="menu-title">Roles</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}"
                           href="{{ route('user-management.permissions.index') }}">
                            <span class="menu-icon">
                              <i class="fa-sharp-duotone fa-solid fa-key-skeleton-left-right"></i>
                            </span>
                            <span class="menu-title">Permissions</span>
                        </a>
                    </div>
                </div>

            </div>
            @endhasrole

        </div>
    </div>
</div>

