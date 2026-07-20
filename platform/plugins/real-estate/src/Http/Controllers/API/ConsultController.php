<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Enums\ConsultCustomFieldTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\SendConsultRequest;
use Botble\RealEstate\Http\Resources\ConsultResource;
use Botble\RealEstate\Http\Resources\CustomFieldResource;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\ConsultCustomField;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ConsultController extends BaseController
{
    /**
     * Send consultation request
     *
     * @group Real Estate
     * @bodyParam name string required The name of the person. Example: John Doe
     * @bodyParam email string required The email address. Example: john@example.com
     * @bodyParam phone string The phone number. Example: +1234567890
     * @bodyParam content string required The consultation message. Example: I'm interested in this property.
     * @bodyParam type string required The type of consultation (property or project). Example: property
     * @bodyParam data_id integer required The ID of the property or project. Example: 1
     * @bodyParam consult_custom_fields array Custom field values.
     */
    public function store(SendConsultRequest $request)
    {
        if (! RealEstateHelper::isEnabledConsultForm()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Consultation form is disabled');
        }

        try {
            $sendTo = null;
            $link = null;
            $subject = null;

            if ($request->input('type') == 'project') {
                $request->merge(['project_id' => $request->input('data_id')]);
                $project = Project::query()
                    ->where('id', $request->input('data_id'))
                    ->with('author')
                    ->first();

                if ($project) {
                    $link = $project->url;
                    $subject = $project->name;

                    if ($project->author?->email) {
                        $sendTo = $project->author->email;
                    }
                }
            } else {
                $request->merge(['property_id' => $request->input('data_id')]);
                $property = Property::query()
                    ->where('id', $request->input('data_id'))
                    ->with('author')
                    ->first();

                if ($property) {
                    $link = $property->url;
                    $subject = $property->name;

                    if ($property->author?->email) {
                        $sendTo = $property->author->email;
                    }
                }
            }

            $data = [
                ...$request->input(),
                'ip_address' => $request->ip(),
            ];

            if (Arr::has($data, 'consult_custom_fields')) {
                $customFields = ConsultCustomField::query()
                    ->wherePublished()
                    ->with('options')
                    ->get();

                $data['custom_fields'] = collect($data['consult_custom_fields'])
                    ->mapWithKeys(function ($item, $id) use ($customFields) {
                        $field = $customFields->firstWhere('id', $id);
                        $option = $field->options->firstWhere('value', $item);

                        if (! $field) {
                            return [];
                        }

                        $value = match ($field->type->getValue()) {
                            ConsultCustomFieldTypeEnum::CHECKBOX => $item ? trans('plugins/real-estate::real-estate.yes') : trans('plugins/real-estate::real-estate.no'),
                            ConsultCustomFieldTypeEnum::RADIO, ConsultCustomFieldTypeEnum::DROPDOWN => $option?->label,
                            default => $item,
                        };

                        return [$field->name => $value];
                    })->all();
            }

            $consult = Consult::query()->create($data);

            $emailHandler = EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'consult_name' => $consult->name,
                    'consult_email' => $consult->email,
                    'consult_phone' => $consult->phone,
                    'consult_content' => $consult->content,
                    'consult_link' => $link,
                    'consult_subject' => $subject,
                    'consult_ip_address' => $consult->ip_address,
                    'consult_custom_fields' => $data['custom_fields'] ?? [],
                ]);

            // Send notification to admin/agent
            if ($sendTo) {
                $emailHandler->sendUsingTemplate('notice', $sendTo);
            }

            // Send confirmation to the person who submitted the consult request
            if ($consult->email) {
                $emailHandler->sendUsingTemplate('sender-confirmation', $consult->email);
            }

            return $this
                ->httpResponse()
                ->setData(new ConsultResource($consult))
                ->setMessage(trans('plugins/real-estate::consult.email.success'))
                ->toApiResponse();
        } catch (Exception $exception) {
            BaseHelper::logError($exception);

            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/real-estate::consult.email.failed'));
        }
    }

    /**
     * Get consultation custom fields
     *
     * @group Real Estate
     */
    public function getCustomFields()
    {
        if (! RealEstateHelper::isEnabledConsultForm()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Consultation form is disabled');
        }

        $customFields = ConsultCustomField::query()
            ->wherePublished()
            ->with('options')
            ->oldest('order')
            ->get();

        return $this
            ->httpResponse()
            ->setData(CustomFieldResource::collection($customFields))
            ->toApiResponse();
    }
}
