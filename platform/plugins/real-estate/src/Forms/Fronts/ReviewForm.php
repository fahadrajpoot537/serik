<?php

namespace Botble\RealEstate\Forms\Fronts;

use Botble\Base\Forms\FieldOptions\ButtonFieldOption;
use Botble\Base\Forms\FieldOptions\LabelFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\LabelField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\RealEstate\Http\Requests\ReviewRequest;
use Botble\Theme\FormFront;

class ReviewForm extends FormFront
{
    public function setup(): void
    {
        $alreadyReviewed = auth('account')->check() && auth('account')->user()->canReview($this->getData('model'));

        $this
            ->contentOnly()
            ->formClass('review-form')
            ->setValidatorClass(ReviewRequest::class)
            ->add(
                'star',
                SelectField::class,
                SelectFieldOption::make()
                    ->choices(collect(range(1, 5))->mapWithKeys(fn ($i) => [$i => $i])->all())
                    ->defaultValue(5)
                    ->label(false)
                    ->attributes(['class' => 'star-rating'])
                    ->when(! $alreadyReviewed, fn (SelectFieldOption $option) => $option->disabled())
            )
            ->add(
                'content',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(trans('plugins/real-estate::review.review_label'))
                    ->placeholder(trans('plugins/real-estate::review.write_comment'))
                    ->when(! $alreadyReviewed, fn (TextareaFieldOption $option) => $option->disabled())
                    ->required()
            )
            ->add(
                'submit',
                'submit',
                ButtonFieldOption::make()
                    ->label(trans('plugins/real-estate::review.send_review'))
                    ->when(! $alreadyReviewed, fn (ButtonFieldOption $option) => $option->disabled())
            )
            ->when(
                ! auth('account')->check(),
                function (ReviewForm $form): void {
                    $form->add(
                        'login_notice',
                        LabelField::class,
                        LabelFieldOption::make()
                            ->wrapperAttributes(['class' => 'mt-3 text-danger'])
                            ->label(trans('plugins/real-estate::review.login_required'))
                    );
                }
            );
    }
}
