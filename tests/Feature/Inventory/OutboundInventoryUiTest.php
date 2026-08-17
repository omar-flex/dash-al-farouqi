<?php

namespace Tests\Feature\Inventory;

use App\Models\Outbound;
use App\Models\OutboundStatus;
use Illuminate\Auth\GenericUser;
use Tests\TestCase;

class OutboundInventoryUiTest extends TestCase
{
    public function test_product_controls_require_permission_and_an_editable_inventory_status(): void
    {
        foreach ([
            [OutboundStatus::WH_RELEASE_PRODUCT, true, true],
            [OutboundStatus::NEED_REVISION, true, true],
            [OutboundStatus::VALIDATION, true, false],
            [OutboundStatus::AUTHORIZATION, true, false],
            [OutboundStatus::WH_RELEASE_PRODUCT, false, false],
        ] as [$status, $canEdit, $shouldShowControls]) {
            $html = $this->renderProducts($status, $canEdit);

            if ($shouldShowControls) {
                $this->assertStringContainsString('id="products-submit"', $html);
                $this->assertStringContainsString('id="products-draft"', $html);
                $this->assertStringContainsString('data-repeater-products-create', $html);
            } else {
                $this->assertStringNotContainsString('id="products-submit"', $html);
                $this->assertStringNotContainsString('id="products-draft"', $html);
                $this->assertStringNotContainsString('data-repeater-products-create', $html);
            }
        }
    }

    public function test_product_and_validation_forms_have_isolated_controls_and_safe_error_targets(): void
    {
        $this->actingAs(new OutboundInventoryUiUser(true));
        $this->app['view']->flushState();

        $outbound = $this->outbound(OutboundStatus::NEED_REVISION);
        $shared = [
            'outbound' => $outbound,
            'warehouseItems' => collect(),
        ];

        $products = view('pages.apps.operation-management.outbounds.sections.packages', $shared + [
            'selectableWarehouseItems' => collect(),
            'cars' => collect(),
        ])->render();
        $validations = view('pages.apps.operation-management.outbounds.sections.validations', $shared)->render();
        $scripts = file_get_contents(resource_path('views/pages/apps/operation-management/outbounds/sections/packages.blade.php'))
            .file_get_contents(resource_path('views/pages/apps/operation-management/outbounds/sections/validations.blade.php'));

        $html = $products.$validations;

        $this->assertStringContainsString('id="product-errors"', $html);
        $this->assertStringContainsString('id="validation-errors"', $html);
        $this->assertStringContainsString('id="products-submit"', $html);
        $this->assertStringContainsString('id="validations-submit"', $html);
        $this->assertStringNotContainsString('id="btn-submit"', $html);
        $this->assertStringNotContainsString('id="btn-draft"', $html);
        $this->assertStringContainsString("$('#formProducts').find('[data-product-action]')", $scripts);
        $this->assertStringContainsString("$('#formValidations').find('[data-validation-action]')", $scripts);
        $this->assertStringContainsString('text: String(message)', $scripts);
    }

    public function test_dynamic_product_selects_are_initialized_only_by_the_local_repeater_script(): void
    {
        $html = $this->renderProducts(OutboundStatus::WH_RELEASE_PRODUCT, true);
        $template = file_get_contents(resource_path(
            'views/pages/apps/operation-management/outbounds/sections/products_items.blade.php'
        ));
        $scriptTemplate = file_get_contents(resource_path(
            'views/pages/apps/operation-management/outbounds/sections/packages.blade.php'
        ));

        $this->assertStringContainsString('class="form-select form-select-solid-bg form-select-sm mb-2 warehouse-items"', $html);
        $this->assertStringContainsString('class="form-select form-select-solid-bg form-select-sm mb-2 cars"', $html);
        $this->assertStringNotContainsString('data-control="select2"', $template);
        $this->assertStringContainsString('initProductSelects();', $scriptTemplate);
    }

    public function test_car_controls_require_edit_permission_and_car_check_status(): void
    {
        foreach ([
            [OutboundStatus::CAR_CHECK, true, true],
            [OutboundStatus::WH_RELEASE_PRODUCT, true, false],
            [OutboundStatus::CAR_CHECK, false, false],
        ] as [$status, $canEdit, $shouldEnableControls]) {
            $this->actingAs(new OutboundInventoryUiUser($canEdit));
            $this->app['view']->flushState();

            $html = view('pages.apps.operation-management.outbounds.sections.cares', [
                'outbound' => $this->outbound($status),
                'cars' => collect(),
            ])->render();

            if ($shouldEnableControls) {
                $this->assertStringContainsString('id="cars-submit"', $html);
                $this->assertDoesNotMatchRegularExpression('/name="numbers\[\]"\s+disabled/', $html);
            } else {
                $this->assertStringNotContainsString('id="cars-submit"', $html);
                $this->assertMatchesRegularExpression('/name="numbers\[\]"\s+disabled/', $html);
            }
        }
    }

    private function renderProducts(int $status, bool $canEdit): string
    {
        $this->actingAs(new OutboundInventoryUiUser($canEdit));
        $this->app['view']->flushState();

        return view('pages.apps.operation-management.outbounds.sections.packages', [
            'outbound' => $this->outbound($status),
            'warehouseItems' => collect(),
            'selectableWarehouseItems' => collect(),
            'cars' => collect(),
        ])->render();
    }

    private function outbound(int $status): Outbound
    {
        return new Outbound([
            'id' => 99,
            'status_id' => $status,
            'quantity_packages' => 10,
            'quantity_car' => 2,
            'total_cost' => 100,
            'gross_weight' => 50,
            'net_weight' => 40,
        ]);
    }
}

class OutboundInventoryUiUser extends GenericUser
{
    public function __construct(private readonly bool $canEdit)
    {
        parent::__construct(['id' => 1, 'name' => 'Inventory UI User']);
    }

    public function can($abilities, $arguments = []): bool
    {
        return $this->canEdit && $abilities === 'edit_outbounds';
    }
}
