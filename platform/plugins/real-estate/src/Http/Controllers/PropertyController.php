<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\PropertyForm;
use Botble\RealEstate\Http\Requests\PropertyRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\SaveFacilitiesService;
use Botble\RealEstate\Services\SavePropertyCustomFieldService;
use Botble\RealEstate\Services\StorePropertyCategoryService;
use Botble\RealEstate\Tables\PropertyTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->breadcrumb()
            ->add(trans('plugins/real-estate::property.name'), route('property.index'));
    }

    public function index(PropertyTable $dataTable)
    {
        $this->pageTitle(trans('plugins/real-estate::property.name'));

        return $dataTable->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/real-estate::property.create'));

        return PropertyForm::create()->renderForm();
    }

    public function store(
        PropertyRequest $request,
        StorePropertyCategoryService $propertyCategoryService,
        SaveFacilitiesService $saveFacilitiesService,
        SavePropertyCustomFieldService $savePropertyCustomFieldService
    ) {
        $request->merge([
            'expire_date' => Carbon::now()->addDays(RealEstateHelper::propertyExpiredDays()),
            'images' => array_filter($request->input('images', [])),
            'author_type' => Account::class,
        ]);

        $propertyForm = PropertyForm::create()->setRequest($request);

        $propertyForm->saving(function (PropertyForm $form) use ($propertyCategoryService, $saveFacilitiesService, $savePropertyCustomFieldService): void {
            $request = $form->getRequest();

            /**
             * @var Property $property
             */
            $property = $form->getModel();
            $property->fill($request->input());
            $property->moderation_status = ModerationStatusEnum::APPROVED;
            $property->never_expired = $request->input('never_expired');
            $property->featured_priority = $request->input('featured_priority') ?: 0;
            $property->save();

            $form->fireModelEvents($property);

            if (RealEstateHelper::isEnabledCustomFields()) {
                $savePropertyCustomFieldService->execute($property, $request->input('custom_fields', []));
            }

            $property->features()->sync($request->input('features', []));

            $saveFacilitiesService->execute($property, $request->input('facilities', []));
            $propertyCategoryService->execute($request, $property);
        });

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('property.index'))
            ->setNextUrl(route('property.edit', $propertyForm->getModel()->getKey()))
            ->withCreatedSuccessMessage();
    }

    public function edit(int|string $id)
    {
        /**
         * @var Property $property
         */
        $property = Property::query()->with(['features', 'author'])->findOrFail($id);

        Assets::addScriptsDirectly(['vendor/core/plugins/real-estate/js/duplicate-property.js']);

        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $property->name]));

        return PropertyForm::createFromModel($property)->renderForm();
    }

    public function update(
        int|string $id,
        PropertyRequest $request,
        StorePropertyCategoryService $propertyCategoryService,
        SaveFacilitiesService $saveFacilitiesService,
        SavePropertyCustomFieldService $savePropertyCustomFieldService
    ) {
        $property = Property::query()->findOrFail($id);

        PropertyForm::createFromModel($property)
            ->setRequest($request)
            ->saving(function (PropertyForm $form) use ($propertyCategoryService, $saveFacilitiesService, $savePropertyCustomFieldService): void {
                $request = $form->getRequest();

                /**
                 * @var Property $property
                 */
                $property = $form->getModel();
                $property->fill($request->except(['expire_date']));
                $property->author_type = Account::class;
                $property->images = array_filter($request->input('images', []));
                $property->never_expired = $request->input('never_expired');
                $property->featured_priority = $request->input('featured_priority') ?: 0;
                $property->save();

                $form->fireModelEvents($property);

                if (RealEstateHelper::isEnabledCustomFields()) {
                    $savePropertyCustomFieldService->execute($property, $request->input('custom_fields', []));
                }

                $property->features()->sync($request->input('features', []));

                $saveFacilitiesService->execute($property, $request->input('facilities', []));
                $propertyCategoryService->execute($request, $property);
            });

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('property.index'))
            ->setNextUrl(route('property.edit', $property->getKey()))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Property $property)
    {
        return DeleteResourceAction::make($property);
    }

    public function approve(Property $property)
    {
        abort_unless($property->is_pending_moderation, 404);

        $property->moderation_status = ModerationStatusEnum::APPROVED;
        $property->save();

        EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'author_name' => $property->author->name,
                'property_name' => $property->name,
                'property_link' => route('public.account.properties.edit', $property->getKey()),
            ])
            ->sendUsingTemplate('property-approved', $property->author->email);

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('property.index'))
            ->setMessage(trans('plugins/real-estate::property.status_moderation.approved'));
    }

    public function reject(Property $property, Request $request)
    {
        abort_unless($property->is_pending_moderation, 404);

        $request->validate([
            'reason' => ['required', 'string', 'max:400'],
        ]);

        $property->moderation_status = ModerationStatusEnum::REJECTED;
        $property->reject_reason = $request->input('reason');
        $property->save();

        EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'author_name' => $property->author->name,
                'property_name' => $property->name,
                'property_link' => route('public.account.properties.edit', $property->getKey()),
                'reason' => $request->input('reason'),
            ])
            ->sendUsingTemplate('property-rejected', $property->author->email);

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('property.index'))
            ->setMessage(trans('plugins/real-estate::property.status_moderation.rejected'));
    }
}
