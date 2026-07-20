<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Forms\FieldOptions\ContentFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\FormFieldOptions;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fields\CustomEditorField;
use Botble\RealEstate\Forms\Fields\MultipleUploadField;
use Botble\RealEstate\Http\Requests\AccountPropertyRequest;
use Botble\RealEstate\Models\Property;

class AccountPropertyForm extends PropertyForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->model(Property::class)
            ->template('plugins/real-estate::account.forms.base')
            ->hasFiles()
            ->setValidatorClass(AccountPropertyRequest::class)
            ->remove('is_featured')
            ->remove('moderation_status')
            ->remove('content')
            ->remove('images[]')
            ->remove('never_expired')
            ->modify(
                'auto_renew',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/real-estate::property.renew_notice', [
                        'days' => RealEstateHelper::propertyExpiredDays(),
                    ]))
                    ->defaultValue(false),
                true
            )
            ->remove('author_id')
            ->addAfter(
                'description',
                'content',
                CustomEditorField::class,
                ContentFieldOption::make()
                    ->label(trans('plugins/real-estate::property.form.content'))
                    ->required()
            )
            ->addAfter(
                'content',
                'images',
                MultipleUploadField::class,
                FormFieldOptions::make()
                    ->label(trans('plugins/real-estate::account-property.images', [
                        'max' => RealEstateHelper::maxPropertyImagesUploadByAgent(),
                    ]))
            );
    }
}
